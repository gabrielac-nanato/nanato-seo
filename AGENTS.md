# AGENTS.md

## Project Snapshot
- WordPress plugin for JSON-LD/schema.org output for legal services sites.
- Current implementation status is early scaffold: key architecture and conventions are documented, but several PHP/plugin bootstrap files are placeholders.
- Primary source of truth for architecture and decisions: [CLAUDE.md](CLAUDE.md).

## Read First
- Architecture and open decisions: [CLAUDE.md](CLAUDE.md)
- Scope and product framing: [.docs/project-scope.md](.docs/project-scope.md)
- Page-type schema mapping: [.docs/schema-markup-mapping.md](.docs/schema-markup-mapping.md)
- Project coding standards: [.docs/CODE_STANDARDS.md](.docs/CODE_STANDARDS.md)
- ACF model draft and OPEN items: [.claude/rules/acf-field-model.md](.claude/rules/acf-field-model.md)
- PHP conventions: [.claude/rules/php-conventions.md](.claude/rules/php-conventions.md)

## Non-Negotiables
- Treat this as a schema architecture/code project, not a content-writing task.
- Do not silently resolve OPEN gaps listed in [CLAUDE.md](CLAUDE.md); surface tradeoffs first.
- Before implementing Layer 1/2/3 ACF field groups, review ACF Pro 6.8 native JSON-LD/schema capabilities (per [.claude/rules/acf-field-model.md](.claude/rules/acf-field-model.md)).
- Keep all schema output filterable via `nanato_seo_*` WordPress hooks.

## Coding Conventions
- Project conventions are defined in [.docs/CODE_STANDARDS.md](.docs/CODE_STANDARDS.md).
- PHP-specific rules are defined in [.claude/rules/php-conventions.md](.claude/rules/php-conventions.md).
- Autoloading is PSR-4: `Nanato_SEO\\` -> `classes/` (see [composer.json](composer.json)).
- Build JSON-LD as PHP arrays and output via `wp_json_encode()`.

## Working Commands
- JS dev watch: `npm run wp:start`
- JS build: `npm run wp:build`
- JS lint: `npm run wp:lint:js`
- CSS lint: `npm run wp:lint:css`
- PHP lint: `composer run lint`
- PHP format: `composer run format`

## Agent Workflow
- Ask before running build/compile commands (`npm run wp:start`, `npm run wp:build`, lint/format commands), because a dev process may already be active.
- Prefer minimal, in-place edits over creating helper files.
- Preserve user changes in a dirty worktree; do not revert unrelated modifications.

## Current Repo Pitfalls
- Multiple expected entry files are currently empty placeholders: [nanato-seo.php](nanato-seo.php), [index.php](index.php), [uninstall.php](uninstall.php), [README.md](README.md), [phpcs.xml.dist](phpcs.xml.dist).
- Frontend source entry exists at [src/frontend.js](src/frontend.js) but is not included in webpack `entry` in [webpack.config.js](webpack.config.js).
- Release config references likely mismatched paths/names (`mainFile: 'nanato-seo.php'`, `buildDir: 'dist'`) in [wp-release.config.js](wp-release.config.js) while repository currently uses `nanato-seo.php` and `build/`.

## Practical Guidance For New Work
- If adding schema for a page/component:
  1. Classify it into Layer 1, Layer 2, or Layer 3 using [CLAUDE.md](CLAUDE.md).
  2. Check if it touches an OPEN gap first; if yes, stop and request decision.
  3. Map properties using [.docs/schema-markup-mapping.md](.docs/schema-markup-mapping.md).
  4. Apply naming and hook/filter conventions from [.claude/rules/php-conventions.md](.claude/rules/php-conventions.md).
