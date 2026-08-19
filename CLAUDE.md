# nanato-seo — Project Memory

## What This Is
A WordPress plugin that generates JSON-LD (schema.org) markup for legal services websites.
Serves multiple law firm clients — similar page structure, variable content, optional features per client.

**This is a structured-data architecture project, not a content project.**
Stay focused on schema.org types, properties, and JSON-LD architecture.
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

1. **Case results/verdicts** — No dedicated schema.org type. Options: omit structured data (rely on well-marked-up content), or approximate with `ItemList` + `MonetaryAmount` (imperfect fit). Decision required.

2. **Conditional templates** — Sub-service pages and FAQ (page and section) are NOT present on every client. Build per-client flags for active/inactive templates — do not assume a fixed template set.

3. **Multi-location entity structure** — How each location relates to the parent org (`subOrganization`? separate `LocalBusiness`?) is undecided. Do not implement until resolved.

4. **ACF-to-schema field mapping** — How ACF field structures (repeaters, groups, relationship fields) map to the three-layer model is undefined. Review ACF Pro 6.8 native schema.org/JSON-LD features before building — may change build-vs-leverage split significantly.

5. **Combo page differentiation** — Service + Location combo pages must be structured to avoid reading as near-duplicates of the plain service or plain location page.

## Key Reference Resources
Verify from source — do not answer from memory. These are fast-moving/recent tools:
- ACF machine-readable content: https://www.advancedcustomfields.com/resources/acf-machine-readable-content/
- ACF Pro 6.8 beta — schema.org/JSON-LD: https://www.advancedcustomfields.com/resources/acf-pro-6-8-beta-2-schema-org-json-ld-testing-guide/
- ACF — automatic structured data: https://www.advancedcustomfields.com/resources/automatic-structured-data-with-schema-org/
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
- `.claude/rules/php-conventions.md` — PHP-specific implementation detail derived from `.docs/CODE_STANDARDS.md` §2 (namespace/class layout, hooks, security, i18n)
- `.claude/rules/acf-field-model.md` — draft ACF Pro field-group structure per schema layer; provisional pending the ACF Pro 6.8 native JSON-LD review

## Working Style Preferences
- Prefer tables/structured references over prose for schema decisions
- When a new page type or field comes up, classify into the three-layer model before proposing schema
- When schema.org has no clean fit, say so explicitly — do not pick closest approximation without flagging it
- When a claim depends on the linked ACF/WP AI resources, verify against source before answering

## Next Steps (at project start)
- [ ] Review ACF Pro 6.8 native schema.org/JSON-LD capabilities — determine build-vs-leverage split
- [ ] Resolve Case Results schema gap
- [ ] Finalize @graph output structure
- [ ] Define required vs. optional properties per schema type per page type
- [ ] Decide multi-location entity structure
- [ ] Define how extension flags conditional/inactive templates per client
- [ ] Define ACF field structure (repeaters/groups) per schema layer
- [x] Define PHP file naming and ACF field naming conventions — draft in place (`.claude/rules/php-conventions.md`, `.claude/rules/acf-field-model.md`); revisit after the ACF Pro 6.8 review
