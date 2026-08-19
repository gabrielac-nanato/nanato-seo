/**
 * Minimal Notion API client shared by the activity logger and the branch sweep.
 *
 * No npm dependencies: uses the global fetch available in Node 18+.
 * Every non-2xx response throws, so the workflow fails loudly.
 */

const NOTION_API = 'https://api.notion.com/v1';
const NOTION_VERSION = '2022-06-28';

const TOKEN = process.env.NOTION_TOKEN;
const BRANCHES_DB = process.env.NOTION_BRANCHES_DB;
const ACTIVITY_DB = process.env.NOTION_ACTIVITY_DB;

if ( ! TOKEN ) {
    throw new Error( 'NOTION_TOKEN is not set. Add it under Settings > Secrets and variables > Actions.' );
}

if ( ! BRANCHES_DB || ! ACTIVITY_DB ) {
    throw new Error( 'NOTION_BRANCHES_DB and NOTION_ACTIVITY_DB must both be set in the workflow env block.' );
}

/**
 * Perform an authenticated request against the Notion API.
 *
 * @param {string} path   Path relative to the API root, e.g. "/pages".
 * @param {string} method HTTP method.
 * @param {object} body   Optional JSON body.
 * @returns {Promise<object>} Parsed JSON response.
 */
async function notionRequest( path, method = 'GET', body = null ) {
    const response = await fetch( `${ NOTION_API }${ path }`, {
        method,
        headers: {
            'Authorization': `Bearer ${ TOKEN }`,
            'Notion-Version': NOTION_VERSION,
            'Content-Type': 'application/json',
        },
        body: body ? JSON.stringify( body ) : undefined,
    } );

    const text = await response.text();

    if ( ! response.ok ) {
        throw new Error(
            `Notion API ${ method } ${ path } failed with ${ response.status }: ${ text }`
        );
    }

    return text ? JSON.parse( text ) : {};
}

/* -------------------------------------------------------------------------
 * Property builders
 * ---------------------------------------------------------------------- */

const title = ( value ) => ( { title: [ { text: { content: truncate( value, 2000 ) } } ] } );
const text = ( value ) => ( { rich_text: [ { text: { content: truncate( value, 2000 ) } } ] } );
const select = ( value ) => ( { select: { name: value } } );
const number = ( value ) => ( { number: value } );
const url = ( value ) => ( { url: value || null } );
const date = ( value ) => ( { date: value ? { start: value } : null } );
const relation = ( pageId ) => ( { relation: pageId ? [ { id: pageId } ] : [] } );

/**
 * Notion rejects rich text longer than 2000 characters.
 *
 * @param {string} value
 * @param {number} max
 * @returns {string}
 */
function truncate( value, max ) {
    const string = String( value ?? '' );
    return string.length > max ? `${ string.slice( 0, max - 1 ) }…` : string;
}

/**
 * Drop properties whose value is undefined so partial updates stay partial.
 *
 * @param {object} properties
 * @returns {object}
 */
function compact( properties ) {
    return Object.fromEntries(
        Object.entries( properties ).filter( ( [ , value ] ) => value !== undefined )
    );
}

/* -------------------------------------------------------------------------
 * Branches database
 * ---------------------------------------------------------------------- */

/**
 * Look up a branch row by its exact name.
 *
 * @param {string} branchName
 * @returns {Promise<object|null>} The Notion page, or null when absent.
 */
async function findBranch( branchName ) {
    const result = await notionRequest( `/databases/${ BRANCHES_DB }/query`, 'POST', {
        filter: {
            property: 'Branch',
            title: { equals: branchName },
        },
        page_size: 1,
    } );

    return result.results?.[ 0 ] ?? null;
}

/**
 * Create the branch row if it does not exist yet, then apply any updates.
 *
 * Rows are never deleted or archived: a removed branch keeps its history and
 * only its Status changes.
 *
 * @param {string} branchName
 * @param {object} properties Notion property payload to write.
 * @param {object} defaults   Extra properties applied only on first creation.
 * @returns {Promise<object>} The Notion page.
 */
async function upsertBranch( branchName, properties = {}, defaults = {} ) {
    const existing = await findBranch( branchName );
    const repository = process.env.GITHUB_REPOSITORY;

    if ( ! existing ) {
        return notionRequest( '/pages', 'POST', {
            parent: { database_id: BRANCHES_DB },
            properties: compact( {
                'Branch': title( branchName ),
                'Repository': text( repository ),
                'Branch URL': url(
                    `https://github.com/${ repository }/tree/${ encodeURIComponent( branchName ) }`
                ),
                ...defaults,
                ...properties,
            } ),
        } );
    }

    if ( Object.keys( compact( properties ) ).length === 0 ) {
        return existing;
    }

    return notionRequest( `/pages/${ existing.id }`, 'PATCH', {
        properties: compact( properties ),
    } );
}

/**
 * Read the current numeric value of a branch property.
 *
 * @param {object} page
 * @param {string} property
 * @returns {number}
 */
function readNumber( page, property ) {
    return page?.properties?.[ property ]?.number ?? 0;
}

/**
 * Read the current select value of a branch property.
 *
 * @param {object} page
 * @param {string} property
 * @returns {string|null}
 */
function readSelect( page, property ) {
    return page?.properties?.[ property ]?.select?.name ?? null;
}

/* -------------------------------------------------------------------------
 * Activity Log database
 * ---------------------------------------------------------------------- */

/**
 * Append a row to the Activity Log.
 *
 * @param {object} entry
 * @returns {Promise<object>}
 */
async function logActivity( entry ) {
    return notionRequest( '/pages', 'POST', {
        parent: { database_id: ACTIVITY_DB },
        properties: compact( {
            'Event': title( entry.event ),
            'Type': select( entry.type ),
            'Branch': entry.branchPageId ? relation( entry.branchPageId ) : undefined,
            'Timestamp': date( entry.timestamp ),
            'Author': entry.author ? text( entry.author ) : undefined,
            'Commit SHA': entry.sha ? text( entry.sha ) : undefined,
            'Commit Message': entry.message ? text( entry.message ) : undefined,
            'Push ID': entry.pushId ? text( entry.pushId ) : undefined,
            'Push Size': typeof entry.pushSize === 'number' ? number( entry.pushSize ) : undefined,
            'PR Number': typeof entry.prNumber === 'number' ? number( entry.prNumber ) : undefined,
            'URL': entry.url ? url( entry.url ) : undefined,
            'Repository': text( process.env.GITHUB_REPOSITORY ),
        } ),
    } );
}

export {
    notionRequest,
    findBranch,
    upsertBranch,
    logActivity,
    readNumber,
    readSelect,
    title,
    text,
    select,
    number,
    url,
    date,
    relation,
    BRANCHES_DB,
    ACTIVITY_DB,
};
