/**
 * Nightly sweep: flips branches with no commits in the last N days to Inactive.
 *
 * Branches marked Merged, Deleted, or Default are left alone. A later push
 * flips a branch back to Active via the activity logger.
 */

import {
    notionRequest,
    upsertBranch,
    logActivity,
    select,
    BRANCHES_DB,
} from './notion-client.mjs';

const INACTIVE_AFTER_DAYS = Number( process.env.INACTIVE_AFTER_DAYS ?? 14 );

const cutoff = new Date( Date.now() - INACTIVE_AFTER_DAYS * 24 * 60 * 60 * 1000 );
const cutoffIso = cutoff.toISOString();

console.log(
    `Marking branches inactive with no activity since ${ cutoffIso } ` +
    `(${ INACTIVE_AFTER_DAYS } days).`
);

/**
 * Page through every Active branch whose last activity predates the cutoff.
 *
 * @returns {Promise<object[]>}
 */
async function findStaleBranches() {
    const results = [];
    let cursor;

    do {
        const response = await notionRequest( `/databases/${ BRANCHES_DB }/query`, 'POST', {
            filter: {
                and: [
                    { property: 'Status', select: { equals: 'Active' } },
                    { property: 'Last Activity', date: { before: cutoffIso } },
                ],
            },
            page_size: 100,
            start_cursor: cursor,
        } );

        results.push( ...( response.results ?? [] ) );
        cursor = response.has_more ? response.next_cursor : undefined;
    } while ( cursor );

    return results;
}

const stale = await findStaleBranches();

if ( stale.length === 0 ) {
    console.log( 'No stale branches found.' );
    process.exit( 0 );
}

for ( const page of stale ) {
    const branchName = page.properties?.Branch?.title?.[ 0 ]?.plain_text;

    if ( ! branchName ) {
        console.log( `Skipping row ${ page.id }: no branch name.` );
        continue;
    }

    const lastActivity = page.properties?.[ 'Last Activity' ]?.date?.start ?? 'unknown';

    await upsertBranch( branchName, { 'Status': select( 'Inactive' ) } );

    await logActivity( {
        event: `Marked inactive: ${ branchName }`,
        type: 'Marked Inactive',
        branchPageId: page.id,
        timestamp: new Date().toISOString(),
        author: 'automation',
        message: `No commits since ${ lastActivity } (threshold ${ INACTIVE_AFTER_DAYS } days).`,
    } );

    console.log( `Marked "${ branchName }" inactive (last activity ${ lastActivity }).` );
}

console.log( `Sweep complete. ${ stale.length } branch(es) marked inactive.` );
