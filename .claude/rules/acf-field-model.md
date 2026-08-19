# ACF Field Model — nanato-seo

Maps the three schema layers (see `CLAUDE.md`) to concrete ACF Pro field-group structures. This is a first draft — no field groups are implemented yet (no `acf-json/` in this repo), and several points below are proposals, not decisions. Flagged items marked **OPEN** must not be silently resolved by implementation; surface them first.

## Before building any of this
**OPEN — build-vs-leverage split.** ACF Pro 6.8 introduced native schema.org/JSON-LD field mapping. Review it against source (see links in `CLAUDE.md`/`project-scope.md`) before writing custom field groups — native support may replace some or all of what's proposed below. Do not implement Layer 1/2/3 field groups until this is checked.

## Layer 1 — Global / Site-Level
One ACF Options Page per client site (`acf_add_options_page`), field group `Nanato SEO — Global Settings`.

- **Field type per property**: scalar schema properties (`name`, `legalName`, `url`, `telephone`, `email`, `foundingDate`, `priceRange`, `inLanguage`) → plain `text`/`url`/`email`/`date_picker` fields, one field group tab per schema.org entity (`Organization`, `WebSite`).
- **Repeaters** for multi-value properties: `sameAs` (repeater of `url` sub-fields), `knowsAbout` (repeater of `text`), `areaServed` default (repeater of `text` or `post_object` if locations become their own CPT — see Layer 2 note).
- **Relationship/post_object fields** for anything that should resolve to a page-owned `@id` rather than a literal string — e.g. `founder` → `post_object` pointing at a Team Member post, so the global entity links to a Layer 2 `Person` node rather than duplicating name/title as text.
- **`parentOrganization` / `subOrganization`**: depends on **OPEN — multi-location entity structure** (see below). Do not build this field until that's resolved.
- Breadcrumb config (home label, separator) and technical defaults (`@context` version, `@graph` toggle) → a small dedicated tab, plain text/select fields, not repeaters (fixed, singular values).

## Layer 2 — Page-Specific
One ACF field group per page type, positioned via **Location Rules** (`Post Type == X` / `Page Template == Y`), not a single mega field group — each page type only sees its own fields.

- Field group naming: `Schema — {Page Type}` (e.g. `Schema — Legal Service`, `Schema — Location`, `Schema — Team Member`).
- Every field group includes a `true_false` "Include in JSON-LD" toggle at the top **only where a page type is conditional per client** (sub-service, dedicated FAQ page) — this is how per-client active/inactive templates are flagged (**OPEN** in `CLAUDE.md`, but the toggle mechanism itself is the proposed answer; the taxonomy of *which* templates need it should be reviewed per client, not hardcoded here).
- Page-owned entities that need a stable `@id` (Team Member, Location, Service, sub-service) get their `@id` derived from `get_permalink()` at render time (`{$permalink}#{$type}`) per the two-tier naming rule — not stored as an ACF field. Don't add an ACF field for `@id`; it's computed, not authored.
- Relationship fields link page-specific entities back to Layer 1: e.g. a Location page's `provider` property uses a `post_object`/`relationship` field pointing at the parent Organization only if there's ever more than one organization per install — for the common single-firm case, resolve `provider` to the global `@id` in code, no field needed.
- **Sub-service `isPartOf`**: `post_object` field pointing at the parent Legal Service page.
- **Combo page (`Service` × `Location`)**: two `post_object` fields (`service`, `location`) rather than free-text duplication — the combo page's schema builder resolves both parent entities' data from these relationships. Addresses the **combo-page-differentiation** gap by construction (fields force a real relationship, not copy-pasted values) — but the actual `areaServed`/`provider` output shape is still an **OPEN** design question.

## Layer 3 — Component-Level
Components are **ACF Flexible Content layouts or Blocks reused across page types**, not their own post type — same pattern as the parent theme's flexible-content sections.

- **FAQ section**: repeater field (`question` text, `answer` wysiwyg/textarea) nested wherever the FAQ flexible-content layout/block is placed. The schema builder reads whichever host page has this layout present and nests a `FAQPage`/`mainEntity` block into that page's `@graph` — no separate field group needed beyond the repeater itself.
- **Reviews component**: **OPEN** — depends on whether reviews are pulled live from a Google Reviews API/feed or entered manually via ACF. If manual: repeater (`author`, `rating`, `text`, `date`). If API-fed: no ACF fields at all, just a toggle to include the component and cached API data feeds the `AggregateRating`/`Review` nodes. Do not build the repeater until this is confirmed.
- **Case results component**: blocked entirely on **OPEN — Case Results schema gap** (no schema.org type fits). Do not build ACF fields for this until Layer 3's schema approach is decided — building the field structure first would implicitly pre-decide the schema representation.

## General field-naming convention
- ACF field names: `snake_case`, prefixed by schema property where it maps 1:1 (e.g. `schema_service_type` for `serviceType`) so the mapping from field to JSON-LD property is greppable.
- Field group keys/names: prefix `group_nanato_seo_...` to avoid collisions with the parent theme's own ACF field groups (`acf-theme-settings.php`, etc.) in the same install.
- Sync exports land in this plugin's own `acf-json/` (create when the first field group is built) — do not hand-edit that JSON once it exists, same rule as the parent theme.

## Status
Entirely provisional. Nothing here should be treated as final until: (1) ACF Pro 6.8 native JSON-LD capabilities are reviewed, (2) the Reviews/Case-Results open gaps are resolved, (3) the multi-location entity structure is decided.
