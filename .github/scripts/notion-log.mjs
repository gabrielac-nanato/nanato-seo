/**
 * Mirrors GitHub repository activity into two Notion databases.
 *
 * Handled events: create, delete, push, pull_request, release.
 * Every commit becomes one Activity Log row; commits pushed together share a
 * Push ID so they can be grouped in Notion.
 */

import { readFileSync } from 'node:fs';
import {
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
} from './notion-client.mjs';

/**
 * Branches matching any of these patterns are ignored entirely.
 * Add or remove entries here — no other file needs to change.
 */
const IGNORED_BRANCH_PATTERNS = [
    /^dependabot\//,
    /^renovate\//,
    /^gh-pages$/,
    /^snyk-/,
    /^whitesource\//,
    /^all-contributors\//,
];

const eventName = process.env.GITHUB_EVENT_NAME;
const payload = JSON.parse( readFileSync( process.env.GITHUB_EVENT_PATH, 'utf8' ) );
const repository = process.env.GITHUB_REPOSITORY;
const defaultBranch = payload.repository?.default_branch ?? 'main';

/**
 * @param {string} branchName
 * @returns {boolean}
 */
function isIgnored( branchName ) {
    return IGNORED_BRANCH_PATTERNS.some( ( pattern ) => pattern.test( branchName ) );
}

/**
 * @param {string} sha
 * @returns {string}
 */
function shortSha( sha ) {
    return String( sha ?? '' ).slice( 0, 7 );
}

/**
 * First line of a commit message, which is what reads well in a table.
 *
 * @param {string} message
 * @returns {string}
 */
function subjectLine( message ) {
    return String( message ?? '' ).split( '\n' )[ 0 ].trim();
}

/**
 * Status a branch should carry after normal activity.
 * The default branch keeps its own status so it never gets swept as inactive.
 *
 * @param {string} branchName
 * @returns {string}
 */
function activeStatusFor( branchName ) {
    return branchName === defaultBranch ? 'Default' : 'Active';
}

/* -------------------------------------------------------------------------
 * Event handlers
 * ---------------------------------------------------------------------- */

/**
 * A branch (or tag) was created.
 */
async function handleCreate() {
    if ( payload.ref_type !== 'branch' ) {
        console.log( `Ignoring create event for ref_type "${ payload.ref_type }".` );
        return;
    }

    const branchName = payload.ref;

    if ( isIgnored( branchName ) ) {
        console.log( `Branch "${ branchName }" matches an ignore pattern. Skipping.` );
        return;
    }

    const now = new Date().toISOString();
    const author = payload.sender?.login ?? 'unknown';

    const page = await upsertBranch( branchName, {
        'Status': select( activeStatusFor( branchName ) ),
        'Created By': text( author ),
        'Created At': date( now ),
        'Last Activity': date( now ),
    } );

    await logActivity( {
        event: `Branch created: ${ branchName }`,
        type: 'Branch Created',
        branchPageId: page.id,
        timestamp: now,
        author,
        url: `https://github.com/${ repository }/tree/${ encodeURIComponent( branchName ) }`,
    } );

    console.log( `Logged branch creation: ${ branchName }` );
}

/**
 * A branch (or tag) was deleted. The row is kept; only the status changes.
 */
async function handleDelete() {
    if ( payload.ref_type !== 'branch' ) {
        console.log( `Ignoring delete event for ref_type "${ payload.ref_type }".` );
        return;
    }

    const branchName = payload.ref;

    if ( isIgnored( branchName ) ) {
        console.log( `Branch "${ branchName }" matches an ignore pattern. Skipping.` );
        return;
    }

    const existing = await findBranch( branchName );

    if ( ! existing ) {
        console.log( `No Notion row for deleted branch "${ branchName }". Nothing to update.` );
        return;
    }

    const now = new Date().toISOString();
    const author = payload.sender?.login ?? 'unknown';

    // A branch deleted after its PR merged stays "Merged" — that reads better
    // than "Deleted" when reviewing history.
    const wasMerged = readSelect( existing, 'Status' ) === 'Merged';

    await upsertBranch( branchName, {
        'Status': wasMerged ? undefined : select( 'Deleted' ),
        'Deleted At': date( now ),
    } );

    await logActivity( {
        event: `Branch deleted: ${ branchName }`,
        type: 'Branch Deleted',
        branchPageId: existing.id,
        timestamp: now,
        author,
    } );

    console.log( `Logged branch deletion: ${ branchName }` );
}

/**
 * Commits were pushed. One Activity Log row per commit.
 */
async function handlePush() {
    if ( ! payload.ref?.startsWith( 'refs/heads/' ) ) {
        console.log( `Ignoring push to non-branch ref "${ payload.ref }".` );
        return;
    }

    if ( payload.deleted ) {
        console.log( 'Push event is a branch deletion; handled by the delete event.' );
        return;
    }

    const branchName = payload.ref.replace( 'refs/heads/', '' );

    if ( isIgnored( branchName ) ) {
        console.log( `Branch "${ branchName }" matches an ignore pattern. Skipping.` );
        return;
    }

    const commits = ( payload.commits ?? [] ).filter( ( commit ) => commit.distinct !== false );

    if ( commits.length === 0 ) {
        console.log( 'Push contained no new distinct commits. Nothing to log.' );
        return;
    }

    const pushId = shortSha( payload.after );
    const headCommit = commits[ commits.length - 1 ];

    // Make sure the row exists before writing relations to it. Branches pushed
    // before this workflow landed on the default branch arrive here first.
    const existing = await findBranch( branchName );
    const page = existing ?? await upsertBranch( branchName, {
        'Status': select( activeStatusFor( branchName ) ),
        'Created By': text( payload.sender?.login ?? 'unknown' ),
        'Created At': date( commits[ 0 ].timestamp ),
    } );

    for ( const commit of commits ) {
        const author = commit.author?.username ?? commit.author?.name ?? 'unknown';

        await logActivity( {
            event: `${ shortSha( commit.id ) } ${ subjectLine( commit.message ) }`,
            type: 'Commit',
            branchPageId: page.id,
            timestamp: commit.timestamp,
            author,
            sha: commit.id,
            message: commit.message,
            pushId,
            pushSize: commits.length,
            url: commit.url,
        } );
    }

    const previousCount = readNumber( page, 'Commit Count' );
    const currentStatus = readSelect( page, 'Status' );

    await upsertBranch( branchName, {
        // Reactivate on new work, but never overwrite Merged or Default.
        'Status': [ 'Merged', 'Default' ].includes( currentStatus )
            ? undefined
            : select( activeStatusFor( branchName ) ),
        'Last Activity': date( headCommit.timestamp ),
        'Last Commit By': text( headCommit.author?.username ?? headCommit.author?.name ?? 'unknown' ),
        'Last Commit SHA': text( shortSha( headCommit.id ) ),
        'Last Commit Message': text( subjectLine( headCommit.message ) ),
        'Commit Count': number( previousCount + commits.length ),
    } );

    console.log( `Logged ${ commits.length } commit(s) on "${ branchName }" (push ${ pushId }).` );
}

/**
 * A pull request was opened, updated, merged, or closed.
 */
async function handlePullRequest() {
    const pullRequest = payload.pull_request;
    const branchName = pullRequest.head?.ref;

    if ( ! branchName || isIgnored( branchName ) ) {
        console.log( `Branch "${ branchName }" is ignored or missing. Skipping.` );
        return;
    }

    const action = payload.action;
    const merged = Boolean( pullRequest.merged );
    const mergedIntoDefault = merged && pullRequest.base?.ref === defaultBranch;
    const author = payload.sender?.login ?? pullRequest.user?.login ?? 'unknown';
    const now = new Date().toISOString();

    let type;
    let prState;

    if ( action === 'closed' && merged ) {
        type = 'PR Merged';
        prState = 'Merged';
    } else if ( action === 'closed' ) {
        type = 'PR Closed';
        prState = 'Closed';
    } else if ( [ 'opened', 'reopened', 'ready_for_review' ].includes( action ) ) {
        type = 'PR Opened';
        prState = pullRequest.draft ? 'Draft' : 'Open';
    } else {
        type = 'PR Updated';
        prState = pullRequest.draft ? 'Draft' : 'Open';
    }

    const existing = await findBranch( branchName );
    const page = existing ?? await upsertBranch( branchName, {
        'Status': select( activeStatusFor( branchName ) ),
        'Created By': text( pullRequest.user?.login ?? 'unknown' ),
        'Created At': date( pullRequest.created_at ),
    } );

    await upsertBranch( branchName, {
        'PR Number': number( pullRequest.number ),
        'PR Title': text( pullRequest.title ),
        'PR State': select( prState ),
        'PR URL': url( pullRequest.html_url ),
        'Status': mergedIntoDefault ? select( 'Merged' ) : undefined,
        'Merged At': mergedIntoDefault ? date( pullRequest.merged_at ?? now ) : undefined,
        'Last Activity': date( now ),
    } );

    await logActivity( {
        event: `PR #${ pullRequest.number }: ${ pullRequest.title }`,
        type,
        branchPageId: page.id,
        timestamp: now,
        author,
        prNumber: pullRequest.number,
        url: pullRequest.html_url,
        message: `${ action } — ${ branchName } → ${ pullRequest.base?.ref }`,
    } );

    console.log( `Logged ${ type } for #${ pullRequest.number } on "${ branchName }".` );
}

/**
 * A GitHub Release was published. Attached to the default branch row.
 */
async function handleRelease() {
    const release = payload.release;
    const now = new Date().toISOString();
    const page = await findBranch( defaultBranch );

    await logActivity( {
        event: `Release ${ release.tag_name }`,
        type: 'Release',
        branchPageId: page?.id,
        timestamp: release.published_at ?? now,
        author: release.author?.login ?? payload.sender?.login ?? 'unknown',
        message: release.name ?? release.tag_name,
        url: release.html_url,
    } );

    console.log( `Logged release ${ release.tag_name }.` );
}

/* -------------------------------------------------------------------------
 * Dispatch
 * ---------------------------------------------------------------------- */

const handlers = {
    create: handleCreate,
    delete: handleDelete,
    push: handlePush,
    pull_request: handlePullRequest,
    release: handleRelease,
};

const handler = handlers[ eventName ];

if ( ! handler ) {
    console.log( `No handler for event "${ eventName }". Nothing to do.` );
    process.exit( 0 );
}

await handler();
