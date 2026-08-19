# nanato-seo — Project Memory

## What This Is
A WordPress plugin that centralizes SEO functionality for legal services websites — structured
data (JSON-LD/schema.org) plus non-schema SEO controls such as archive-page noindexing.
Serves multiple law firm clients — similar page structure, variable content, optional features per client.

Originally scoped to schema.org/JSON-LD only (see the three-layer architecture below); broadened
to a general SEO plugin so features like `Noindex_Archive` (folded in from the standalone
`nanato-noindex-archive-pages` plugin) have a home without spinning up a new plugin per feature.

**This is a technical-SEO/structured-data architecture project, not a content project.**
Stay focused on schema.org types/properties, JSON-LD architecture, and similar technical SEO
controls (meta robots, indexing rules, etc.) — not copywriting or content production.
If a task drifts toward content generation, flag the distinction first.

## Goals
- **SEO**: rich-result eligibility — breadcrumbs, FAQ snippets, review stars, Knowledge Panel, sitelinks search box
- **GEO**: structure entity data so AI systems (Google AI Overviews, ChatGPT, Perplexity) can parse, disambiguate, and cite the firm

## Tech Stack
- **WordPress** — CMS
- **ACF Pro (Advanced Custom Fields)** — field management, feeds JSON-LD output
- **PHP** — plugin code
- **Claude** — part of tooling/workflow

## Schema Architecture — Three Layers

### Layer 1: Global / Site-Level (filled once per client)
Global settings panel, referenced everywhere via `@id`:
- `Organization`/`LegalService`: name, legalName, url, logo, image, description, telephone, email, sameAs, foundingDate, founder, knowsAbout, areaServed, priceRange, parentOrganization/subOrganization, inLanguage, contactPoint
- `WebSite`: @id, url, name, publisher, potentialAction (SearchAction)
- Default Publisher block (for Blog/Article)
- Default Author fallback (for unattributed posts)
- Breadcrumb config (home label, URL base)
- Technical defaults: @context, @graph convention, @id naming

### Layer 2: Page-Specific (filled per page instance)
Each page type maps to schema.org types. Full mapping: `docs/schema-markup-mapping.md`

Quick reference:
| Page | Primary schema type(s) |
|------|------------------------|
| Homepage (= locations hub) | `Organization`/`LegalService`, `WebSite`, `BreadcrumbList` |
| About Us | `AboutPage`, `Organization` |
| Team page | `CollectionPage`/`ItemList` of `Person` |
| Team member | `Person` (`jobTitle` distinguishes attorney/paralegal/staff — NOT the schema type) |
| Contact | `ContactPage`, `ContactPoint` |
| Services hub | `CollectionPage`/`ItemList` |
| Service page | `Service`/`LegalService`, optional `FAQPage` |
| Sub-service *(conditional)* | Same + `isPartOf` |
| Service + Location combo | `LegalService` with location-specific `areaServed` |
| Location page | `LegalService`/`LocalBusiness` with `address`, `geo`, hours |
| Blog / CollectionPage | `Blog`/`CollectionPage`, `ItemList` of `BlogPosting` |
| Single post | `BlogPosting`/`Article` |
| Archive by category | `CollectionPage` |
| Resources/Guides | `CollectionPage`, `Article` per guide |
| FAQ page *(conditional)* | `FAQPage` |
| Privacy Policy / ToS | `WebPage` |
| Thank you / 404 | `WebPage` (noindex, minimal markup) |

### Layer 3: Component-Level (filled per instance, merged into host page)
| Component | Schema approach |
|-----------|----------------|
| FAQ section | `FAQPage`/`mainEntity` nested into host page |
| Reviews | `AggregateRating` + `Review`, attached to business entity |
| Case results | **UNRESOLVED — see Open Gaps** |

## @id Naming Convention (Two-Tier Rule)
**Site-wide entities** (Organization, WebSite, Logo, default Publisher/Author) → domain root:
```
https://domain.com/#organization
https://domain.com/#website
```
**Page-owned entities** (team members, locations, services, sub-services) → canonical page URL:
```
https://domain.com/team/jane-doe/#person
https://domain.com/services/car-accident/#service
```
Schema type and @id pattern **never vary by role/category** — `jobTitle` handles that.

## Output Conventions
- Output format: `<script type="application/ld+json">` blocks
- Use `@graph` array for all multi-entity pages
- All schema output should be filterable via WordPress hooks
- PHP file/class naming conventions: drafted in `.claude/rules/php-conventions.md` (`.docs/CODE_STANDARDS.md` §2 is authoritative) — no class has been implemented yet, so treat as provisional until the first one lands
- ACF field naming convention: drafted in `.claude/rules/acf-field-model.md` — provisional pending the ACF Pro 6.8 native JSON-LD review (see Open Gaps below)

## Open Gaps — Do Not Silently Resolve
Flag these before implementing. Do not pick a default without surfacing the tradeoff:

1. **Case results/verdicts** — No dedicated schema.org type. `nanato-seo` will not model these as JSON-LD. Case-result structured markup will be handled through HTML `itemprop` attributes in theme code because case results are not a custom post type.

2. **Conditional templates** — Sub-service pages and FAQ (page and section) are NOT present on every client. FAQ support is planned, with per-client/page inclusion controls still required; do not assume a fixed template set.

3. **ACF-to-schema field mapping** — ACF Pro 6.8.8 is installed and current. The native schema.org/JSON-LD capability still needs to be verified against the official implementation documentation before custom Layer 1/2/3 field groups and renderers are expanded.

4. **Combo page differentiation** — Service + Location combo pages must be structured to avoid reading as near-duplicates of the plain service or plain location page. Partially unblocked by the multi-location decision below (see Resolved Decisions) — combo pages get a `primary_location` field to resolve `provider` to a specific location's `@id`, but the actual `areaServed` output shape per combo page is still open.

5. **omni-schema-panel migration decisions** — `nanato-seo` will fully replace the legacy `omni-schema-panel` plugin (inherited from a prior agency on Ceja). Structured-data functionality is in scope; the visible author chip is an optional feature, not a required part of the first migration. See `.docs/omni-migration-mapping.md` for the remaining field-level questions, including Reviews and the `datePublished`/`dateModified` behavior.

## Resolved Decisions

### Multi-location entity structure (was Open Gap #3)
Decided after checking real client sitemaps/contact pages (Ceja: 2 offices — Pasadena, Houston —
on one shared directions page; Whitley Law Firm (`abogadoswhitley.com`): 7-8 offices — Raleigh,
Kinston, New Bern, Charlotte, Jacksonville, Greenville, Winston-Salem, Durham — all on one shared
Contact page, each just linking out to a Google Maps pin). No current client has dedicated
per-location pages, which rules out a page-owned-entity-per-location model as the default.

- **Layer 1 (global options page): a `locations` repeater**, unbounded rows — must scale from 2
  to 8+ without any hardcoded count. Sub-fields per row: `location_name`, `phone`, `email`,
  `address` (street/city/state/postal_code/country group), `geo` (lat/long group), `map_url`,
  `areas_served` (repeater of text — feeds the combo-page differentiation gap above). Also an
  *optional* `location_page` post_object field — empty for every current client, but present so a
  future client who does build dedicated location pages isn't a special case.
- **A `contact_page` field** on Layer 1 pointing at whichever page is the client's shared
  contact/directions page (`/contacto/`, `/directions/`, `/como-llegar/` all vary by client) —
  falls back to `home_url()` if unset.
- **`@id` is computed at render time, never stored:**
  - If a row's `location_page` is set → `{permalink}#legalservice` (page-owned pattern, for the
    future dedicated-page case).
  - Otherwise → `{contact_page URL}#location-{sanitize_title(location_name)}`.
- **Relationship:** each location's `LegalService` node carries
  `"branchOf": {"@id": "{home_url}/#organization"}`. The global `Organization` node does **not**
  enumerate `subOrganization` back — one-directional `branchOf` matches schema.org's own
  multi-location examples and avoids a second place that has to stay in sync with the repeater.
- **Combo pages** get a `primary_location` field (references one repeater row) so `provider`
  resolves to that specific location's `@id` instead of the generic org `@id`.

### Implementation scope decisions

- **Environment:** WordPress 7.0.4 and ACF Pro 6.8.8 on the local installation at
  `ceja-law-firm.test`. The current milestone does not depend on theme templates or custom
  post types.
- **Implementation order:** global Organization/WebSite schema first, then multi-location
  `LegalService` nodes, followed by page-specific schema.
- **Opening hours:** retain support as an optional global setting. Most client sites will leave
  it unused; do not require hours data for schema output.
- **FAQ:** include FAQ schema support for both FAQ pages and reusable FAQ sections, with
  conditional inclusion controls for clients that do not use them.
- **Reviews:** defer Reviews/AggregateRating until the data source is explicitly selected. Do
  not carry over the legacy uncached live-average query by default.
- **Case results:** keep case-result markup in theme HTML using `itemprop`; do not add a
  case-results schema builder to this plugin.
- **Author chip:** migrate the structured author data, but treat visible author-chip rendering
  as an optional feature that can be enabled separately.

## Key Reference Resources
Verify from source — do not answer from memory. These are fast-moving/recent tools:
- ACF automatic structured data overview: https://www.advancedcustomfields.com/resources/automatic-structured-data-with-schema-org/
- ACF Schema.org property mapping: https://www.advancedcustomfields.com/resources/schema-org-property-mapping/
- ACF structured data for blocks: https://www.advancedcustomfields.com/resources/structured-data-for-acf-blocks/
- ACF machine-readable content: https://www.advancedcustomfields.com/resources/acf-machine-readable-content/
- ACF Schema.org JSON-LD testing guide: https://www.advancedcustomfields.com/resources/acf-pro-6-8-beta-2-schema-org-json-ld-testing-guide/
- ACF schema output format choices: https://www.advancedcustomfields.com/resources/acf-schema-output_format_choices/
- WordPress AI plugin (directory): https://wordpress.org/plugins/ai/
- WordPress AI feature plugin (GitHub): https://github.com/WordPress/ai/blob/develop/README.md
- AI Provider for Anthropic: https://wordpress.org/plugins/ai-provider-for-anthropic/
- PHP AI Client: https://github.com/wordpress/php-ai-client/
- WordPress Abilities API: https://developer.wordpress.org/apis/abilities-api/
- WP AI Contributor weekly summary (Jul 8 2026): https://make.wordpress.org/ai/2026/07/10/ai-contributor-weekly-summary-8-july-2026/

## Related Documentation
- `.docs/CODE_STANDARDS.md` — authoritative team coding standard (PHP, JS, SCSS, ACF, security, i18n, file structure, build/lint commands)
- `.docs/project-scope.md` — full project scope: objectives, page-type taxonomy, three-layer architecture, extension architecture implications
- `.docs/schema-markup-mapping.md` — detailed per-page-type schema.org property mapping, plus GEO-specific notes
- `.docs/omni-migration-mapping.md` — field-by-field mapping from the legacy `omni-schema-panel` plugin's ACF model to `nanato-seo`'s three-layer architecture, for migrating Ceja's existing schema data
- `.claude/rules/php-conventions.md` — PHP-specific implementation detail derived from `.docs/CODE_STANDARDS.md` §2 (namespace/class layout, hooks, security, i18n)
- `.claude/rules/acf-field-model.md` — draft ACF Pro field-group structure per schema layer; provisional pending the ACF Pro 6.8 native JSON-LD review

## Working Style Preferences
- Prefer tables/structured references over prose for schema decisions
- When a new page type or field comes up, classify into the three-layer model before proposing schema
- When schema.org has no clean fit, say so explicitly — do not pick closest approximation without flagging it
- When a claim depends on the linked ACF/WP AI resources, verify against source before answering

## Next Steps (at project start)
- [ ] Verify ACF Pro 6.8.8 native schema.org/JSON-LD capabilities — determine build-vs-leverage split
- [x] Resolve Case Results schema gap — keep case-result `itemprop` markup in theme code
- [ ] Finalize @graph output structure
- [ ] Define required vs. optional properties per schema type per page type
- [x] Decide multi-location entity structure
- [ ] Define how extension flags conditional/inactive templates per client, including FAQ
- [ ] Define ACF field structure (repeaters/groups) per schema layer
- [x] Define PHP file naming and ACF field naming conventions — draft in place (`.claude/rules/php-conventions.md`, `.claude/rules/acf-field-model.md`); revisit after the ACF Pro 6.8 review
