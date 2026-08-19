# **Project Scope — JSON-LD Schema Generator Extension for Legal Services Websites**

## **1\. Overview**

This project defines the structured-data (schema.org / JSON-LD) foundation for a browser extension (or CMS plugin) that generates schema markup for legal services websites. The extension is meant to be reused across multiple client sites (law firms), each with a similar page structure but variable content and optional features (sub-services, FAQ sections, etc.).

**This is a structured-data architecture project, not a content project.** The deliverable of this scoping phase is a reference model of which schema.org types and properties apply to which page types, and which of those fields are global (set once per client) versus page-specific (set per page instance).

## **2\. Tech Stack** {#2.-tech-stack}

* **WordPress** — the CMS the plugin/extension is built for
* **ACF Pro (Advanced Custom Fields)** — the field-management plugin used to build the per-page and global data structures that feed the JSON-LD output
* **Claude** — used as part of the tooling/workflow for this project

### **Reference resources**

Consult these directly when technical specifics are needed — verify against source rather than assuming, since these are fast-moving/recent tools:

* ACF automatic structured data overview: [https://www.advancedcustomfields.com/resources/automatic-structured-data-with-schema-org/](https://www.advancedcustomfields.com/resources/automatic-structured-data-with-schema-org/)
* ACF Schema.org property mapping: [https://www.advancedcustomfields.com/resources/schema-org-property-mapping/](https://www.advancedcustomfields.com/resources/schema-org-property-mapping/)
* ACF structured data for blocks: [https://www.advancedcustomfields.com/resources/structured-data-for-acf-blocks/](https://www.advancedcustomfields.com/resources/structured-data-for-acf-blocks/)
* ACF machine-readable content: [https://www.advancedcustomfields.com/resources/acf-machine-readable-content/](https://www.advancedcustomfields.com/resources/acf-machine-readable-content/)
* ACF Schema.org JSON-LD testing guide: [https://www.advancedcustomfields.com/resources/acf-pro-6-8-beta-2-schema-org-json-ld-testing-guide/](https://www.advancedcustomfields.com/resources/acf-pro-6-8-beta-2-schema-org-json-ld-testing-guide/)
* ACF schema output format choices: [https://www.advancedcustomfields.com/resources/acf-schema-output_format_choices/](https://www.advancedcustomfields.com/resources/acf-schema-output_format_choices/)
* WordPress AI plugin (plugin directory): [https://wordpress.org/plugins/ai/](https://wordpress.org/plugins/ai/)
* WordPress AI feature plugin (GitHub): [https://github.com/WordPress/ai/blob/develop/README.md](https://github.com/WordPress/ai/blob/develop/README.md)
* AI Provider for Anthropic (WordPress plugin): [https://wordpress.org/plugins/ai-provider-for-anthropic/](https://wordpress.org/plugins/ai-provider-for-anthropic/)
* PHP AI Client (GitHub): [https://github.com/wordpress/php-ai-client/](https://github.com/wordpress/php-ai-client/)
* WordPress AI Contributor weekly summary, July 8 2026: [https://make.wordpress.org/ai/2026/07/10/ai-contributor-weekly-summary-8-july-2026/](https://make.wordpress.org/ai/2026/07/10/ai-contributor-weekly-summary-8-july-2026/)
* WordPress Abilities API: [https://developer.wordpress.org/apis/abilities-api/](https://developer.wordpress.org/apis/abilities-api/)

## **3\. Objectives**

* **SEO**: Enable standard rich-result eligibility (breadcrumbs, FAQ rich snippets, review stars, Knowledge Panel data, sitelinks search box) through correct, consistent schema.org markup.
* **GEO (Generative Engine Optimization)**: Structure entity data (firm, attorneys, locations, services) so AI systems (Google AI Overviews, ChatGPT, Perplexity, etc.) can reliably parse, disambiguate, and cite the firm as an authoritative source.
* **Consistency at scale**: Since the extension will serve multiple client sites, avoid re-entering identical data per page — capture site-wide facts once and reference them everywhere via stable `@id` values.

## **4\. Scope**

### **In scope**

* Defining the page-type taxonomy common to legal services websites ([Section 5](#5.-page-type-taxonomy))
* Mapping applicable schema.org types/properties to each page type ([Section 7](#7.-known-gaps-&-open-decisions))
* Identifying which schema fields are **global** (site-level, filled once) vs. **page-specific** (filled per instance) vs. **component-level** (filled per reusable block instance)
* Flagging schema.org coverage gaps relevant to legal services content (e.g., case results/verdicts)
* Establishing conventions the extension needs internally: `@id` naming, `@graph` structure, breadcrumb generation logic

### **Out of Scope**

* Actual page content/copywriting
* Visual design or page layout
* Extension UI/UX design
* Backend implementation, data storage, or CMS integration mechanics
* Google Business Profile or third-party directory management (referenced only via `sameAs`)
* Legal review of Privacy Policy / Terms of Service content

## **5\. Page Type Taxonomy** {#5.-page-type-taxonomy}

### **Core Pages**

* Homepage *(also serves as the locations hub)*
* About Us page
  * Team page (attorney directory, grid layout)
    * Team member single page
* Contact page
* Thank you page
* Search results page
* 404 page

### **Legal Services**

* Legal services hub
  * Legal service single page
    * Legal sub-service single page *(optional — conditional per client)*
  * Legal service \+ Location combo page *(e.g. "Car Accident Lawyer in Dallas" — distinct template for local SEO)*

### **Locations**

* Location page *(one per office/city; no separate hub — homepage covers that role)*

### **Content & Resources**

* Blog
  * Single post page
  * Archive by category
* Resources/Guides page
* FAQ page *(dedicated, standalone — conditional per client)*

### **Legal / Compliance**

* Privacy Policy page
* Terms of Service page

### **Reusable Components (not standalone pages)**

* FAQ section *(embeddable — can appear on service, location, combo, about pages, etc.; conditional per client)*
* Reviews component *(pulls from Google Reviews; used on Homepage, About Us, Contact)*
* Case results component *(verdicts/settlements; used on Homepage, About Us, Contact)*

## **6\. Schema Architecture — Three Layers**

### **Layer 1: Global / Site-Level (filled once per client)**

Set in a global settings panel and referenced everywhere via `@id`:

- Core `Organization`/`LegalService` entity: `name`, `legalName`, `url`, `logo`, `image`, `description`, `telephone`, `email`, `sameAs`, `foundingDate`, `founder`, `knowsAbout`, `areaServed` (default), `priceRange`, `parentOrganization`/`subOrganization`, `inLanguage`, `contactPoint`
- `WebSite` entity: `@id`, `url`, `name`, `publisher`, `potentialAction` (SearchAction)
- Default `Publisher` block for Blog/Article schema
- Default `Author` fallback for unattributed posts
- Breadcrumb generation config (home label, URL base, separator logic)
- Technical defaults: `@context`, `@graph` convention, `@id` naming convention

### **Layer 2: Page-Specific (filled per page instance)**

Each page type maps to specific schema.org types — full mapping in companion document *(schema-markup-mapping.md)*. Summary:

| Page type | Primary schema type(s) |
| :---- | :---- |
| Homepage | `Organization`/`LegalService`, `WebSite`, `BreadcrumbList` |
| About Us | `AboutPage`, `Organization` |
| Team page | `CollectionPage`/`ItemList` of `Person` |
| Team member page | `Person` (any role — `jobTitle` distinguishes attorney vs. paralegal vs. staff, etc.) |
| Contact page | `ContactPage`, `ContactPoint` |
| Legal services hub | `CollectionPage`/`ItemList` |
| Legal service page | `Service`/`LegalService`, optional `FAQPage` |
| Sub-service page | Same as above \+ `isPartOf` |
| Service \+ Location combo | `LegalService` with location-specific `areaServed` |
| Location page | `LegalService`/`LocalBusiness` with `address`, `geo`, hours |
| Blog (all posts) | `Blog`/`CollectionPage`, `ItemList` of `BlogPosting` |
| Single post | `BlogPosting`/`Article` |
| Archive by category | `CollectionPage` |
| Resources/Guides | `CollectionPage`, `Article` per guide |
| FAQ page | `FAQPage` |
| Search results | `SearchResultsPage` |
| Privacy/Terms | `WebPage` (no dedicated type exists) |
| Thank you / 404 | `WebPage` (typically `noindex`, minimal markup) |

### **Layer 3: Component-Level (filled per reusable block instance)**

| Component | Schema approach |
| :---- | :---- |
| FAQ section | `FAQPage`/`mainEntity` nested into host page |
| Reviews | `AggregateRating` \+ `Review`, attached to page's business entity |
| Case results | **Unresolved — see [Section 7](#7.-known-gaps-&-open-decisions)** |

## **7\. Known Gaps & Open Decisions** {#7.-known-gaps-&-open-decisions}

* **Case results / verdicts**: No dedicated schema.org type exists. Needs a deliberate decision — omit structured data and rely on well-marked-up plain content, or approximate with `ItemList` \+ `MonetaryAmount` (imperfect fit either way).
* **Conditional templates**: Sub-service pages and FAQ (both the page and the section) are not present on every client site. The extension needs a way to flag which templates/components are "active" per client rather than assuming a fixed template set.
* **Combo page differentiation**: Legal service \+ Location combo pages must be structured (in schema, not just content) to avoid reading as near-duplicates of the plain service page and plain location page.
* **`@id` naming convention**: Resolved — two-tier rule. Site-wide entities (Organization, WebSite, Logo, default Publisher/Author) anchor to the domain root (`https://domain.com/#organization`). Page-owned entities (any team member regardless of role, Location, Service, sub-service) anchor to their own canonical page URL (`https://domain.com/team/jane-doe/#person`), whatever that site's actual URL structure is. Schema type and `@id` pattern don't vary by role — `jobTitle` handles that distinction for `Person` entities.
* **Multi-location entity structure**: Whether each location is a `LocalBusiness` linked via `subOrganization`, or handled another way, needs a final decision before the Location page schema can be finalized.
* **ACF-to-schema field mapping**: How ACF field structures (repeaters, groups, relationship fields, etc.) should map to the three-layer schema model (global/page/component) is not yet defined. This is likely the next major design step, once the linked ACF resources ([Section 2](#2.-tech-stack)) are reviewed in detail — ACF Pro 6.8's native schema.org/JSON-LD features in particular may change how much of this the extension needs to build versus leverage natively.

## **8\. Extension Architecture Implications**

* **One global settings form** → populates Organization, WebSite, default Publisher/Author, and technical config. Filled once per client.
* **Per-page-type generator forms** → page-specific fields, pulling in the global entity via `@id` reference.
* **Component-level generators** → FAQ, Reviews, Case Results — output fragments merged into whichever host page uses them.

## **9\. Next Steps**

- [ ] Review ACF Pro 6.8 native schema.org/JSON-LD capabilities and determine build-vs-leverage split ([Section 2](#2.-tech-stack), [Section 7](#7.-known-gaps-&-open-decisions))
- [ ] Resolve Case Results schema gap ([Section 7](#7.-known-gaps-&-open-decisions))
- [ ] Finalize `@graph` output structure
- [ ] Define exact required vs. optional properties per schema type, per page type
- [ ] Decide multi-location entity structure
- [ ] Define how the extension flags conditional/inactive templates per client
- [ ] Define ACF field structure (repeaters/groups) per layer of the schema model
