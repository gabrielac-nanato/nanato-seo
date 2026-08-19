# AGENTS.md

## Project Snapshot
- WordPress plugin for SEO controls for legal services sites, including archive noindexing and planned JSON-LD/schema.org output.
- The plugin bootstrap is functional: it loads Composer autoloading when available, falls back to the local class files, loads helpers, and instantiates the plugin classes.
- Implemented features include the Nanato SEO admin menu, archive noindex settings with legacy-option migration, and the ACF global settings page with the multi-location field group.
- Schema graph builders and most page/component field groups are not implemented yet. `Hooks` and `Admin` currently contain empty registration methods, and `uninstall.php` still has a data-cleanup TODO.
- Primary source of truth for architecture and decisions: [CLAUDE.md](CLAUDE.md).

## Read First
- Architecture and open decisions: [CLAUDE.md](CLAUDE.md)
- Scope and product framing: [.docs/project-scope.md](.docs/project-scope.md)
- Page-type schema mapping: [.docs/schema-markup-mapping.md](.docs/schema-markup-mapping.md)
- Project coding standards: [.docs/CODE_STANDARDS.md](.docs/CODE_STANDARDS.md)
- ACF model draft and OPEN items: [.claude/rules/acf-field-model.md](.claude/rules/acf-field-model.md)
- PHP conventions: [.claude/rules/php-conventions.md](.claude/rules/php-conventions.md)

## Non-Negotiables
- Treat this as a technical SEO/schema architecture project, not a content-writing task.
- Do not silently resolve OPEN gaps listed in [CLAUDE.md](CLAUDE.md); surface tradeoffs first.
- Before implementing Layer 1/2/3 ACF field groups, verify ACF Pro 6.8.8 native JSON-LD/schema capabilities against the official documentation (per [.claude/rules/acf-field-model.md](.claude/rules/acf-field-model.md)).
- Keep future schema output deterministic and filterable via `nanato_seo_*` WordPress hooks.
- Preserve the resolved multi-location model: locations are rows in the global ACF options page; location `@id` values and `branchOf` relationships are computed during rendering, not stored as fields.
- Case-result markup belongs in theme HTML using `itemprop`; do not add a case-results schema builder to this plugin.
- FAQ schema is in scope, while Reviews/AggregateRating remains deferred until its data source is selected.
- The legacy omni plugin is being fully replaced for structured data; visible author-chip rendering is optional.

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
- Release packaging is configured in `wp-release.config.js`; verify its `dist` build directory against the webpack output before creating a release.
- Current local validation environment: WordPress 7.0.4 and ACF Pro 6.8.8 at `https://ceja-law-firm.test/wp-admin/network/`.

## Agent Workflow
- Ask before running build/compile commands (`npm run wp:start`, `npm run wp:build`, lint/format commands), because a dev process may already be active.
- Prefer minimal, in-place edits over creating helper files.
- Preserve user changes in a dirty worktree; do not revert unrelated modifications.

## Current Repo Pitfalls
- [README.md](README.md) is empty, and [uninstall.php](uninstall.php) only contains the uninstall guard plus a data-cleanup TODO.
- [src/frontend.js](src/frontend.js) imports the frontend stylesheet but is not included in the webpack `entry` map, so it is not currently built.
- Webpack uses the WordPress scripts defaults, which normally emit to `build/`, while [wp-release.config.js](wp-release.config.js) packages from `dist/`; resolve or verify this before release packaging.
- [classes/Hooks.php](classes/Hooks.php) and [classes/Admin.php](classes/Admin.php) are intentionally scaffolded but have no registered hooks yet; do not assume they provide schema or admin behavior.
- [classes/ACF_Settings.php](classes/ACF_Settings.php) registers local PHP fields rather than loading `acf-json/`; do not hand-create JSON exports unless the project convention changes.

## Practical Guidance For New Work
- If adding schema for a page/component:
  1. Classify it into Layer 1, Layer 2, or Layer 3 using [CLAUDE.md](CLAUDE.md).
  2. Check if it touches an OPEN gap first; if yes, stop and request decision.
  3. Map properties using [.docs/schema-markup-mapping.md](.docs/schema-markup-mapping.md).
  4. Apply naming and hook/filter conventions from [.claude/rules/php-conventions.md](.claude/rules/php-conventions.md).
- For ACF changes, guard calls to ACF APIs with `function_exists()` as the current implementation does, and keep options-page fields under the `nanato-seo-global-settings` options page.
- For archive noindex changes, preserve the `nanato_seo_` option/action names and the one-time migration behavior from the legacy `archive_noindex_options` option.
- Build JSON-LD as PHP arrays and encode only at output time with `wp_json_encode()`; never concatenate JSON strings or echo unescaped field values.
- Before running watch, build, format, or lint commands, check with the user because a development process may already be active.
