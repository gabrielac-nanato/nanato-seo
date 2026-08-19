# Migration Mapping — omni-schema-panel → nanato-seo

**Purpose:** `nanato-seo` is meant to fully replace the legacy `omni-schema-panel` ("Omnizant Schema
Panel") plugin on Ceja Law Firm. That plugin was inherited from another agency; it has useful
schema coverage but unsafe/unmaintainable code (raw string concatenation into JSON-LD, no
escaping, no namespacing — see review notes in project history). This doc maps its ACF field
model onto `nanato-seo`'s three-layer architecture (`CLAUDE.md`) so existing client data isn't
lost or re-entered from scratch when the switch happens, and so gaps between the two models are
surfaced before implementation rather than discovered mid-migration.

**Status:** Reference only — no migration has been implemented. The migration will cover the
legacy structured-data functionality; the visible author chip is optional and is not required
for the first migration milestone.

---

## 1. Global org fields (omni options page → nanato-seo Layer 1)

All read via `get_field( $name, 'options' )` from omni's single ACF options page (`theme-schema`).

| omni field (`acf-export-omni-schema.json`) | schema.org property | nanato-seo Layer 1 target |
|---|---|---|
| `logo_leg_serv` | `Organization.logo` | Global Settings → Organization tab, `logo` |
| `image_leg_serv` | `Organization.image` | Global Settings → Organization tab, `image` |
| `description_leg_serv` | `Organization.description` | Global Settings → Organization tab, `description` |
| `enable_legalservice` | — (feature toggle, not a schema property) | Layer 2 "Include in JSON-LD" toggle pattern, or a Layer 1 site-wide default if every page shares one `LegalService` |
| `phone_leg_serv` | `LegalService.telephone` | Global Settings → Organization tab, `telephone` |
| `price_leg_serv` | `LegalService.priceRange` | Global Settings → Organization tab, `priceRange` |
| `postal_address_leg_serv` (group: `street`/`city`/`state`/`postal_code`/`country`) | `PostalAddress` | Global Settings → `address` group field (mirror the same 5 sub-fields) |
| `geo_coordinates_leg_serv` (group: `latitude`/`longitude`) | `GeoCoordinates` | Global Settings → `geo` group field |
| `map_url_leg_serv` | `hasMap` | Global Settings → `hasMap` (url field) |
| `days_leg_serv` / `opening_leg_serv` / `closing_leg_serv` | `OpeningHoursSpecification` | Global Settings → repeater of `{dayOfWeek[], opens, closes}` — omni's model is a single flat spec applied to all days, not a true per-day repeater; **decide whether to keep that simplification or build a proper multi-row hours repeater** |
| `areas_leg_serv` | `areaServed` | Global Settings → repeater of `text` (per `acf-field-model.md`'s existing `areaServed` proposal) |
| `email_leg_serv` | `email` | Global Settings → Organization tab, `email` |
| `profiles_leg_serv` (repeater, sub-field `link`) | `sameAs` | Global Settings → `sameAs` repeater (already proposed in `acf-field-model.md`) — direct match, no gap |
| `enable_aggregaterating` | — (feature toggle) | Layer 3 "Include Reviews component" toggle |
| `enable_blogposting` | — (feature toggle) | Layer 2 "Include in JSON-LD" toggle on the post-type template, or a global default |
| `enable_author` | — (feature toggle) | Layer 2/3 toggle on post templates |
| `default_author_schema` (post_object → `om_author` CPT) | `author` fallback | Layer 1 "Default Author fallback" (already named as a Layer 1 concept in `CLAUDE.md`) — direct match |

**Decision — hours model.** Retain opening-hours support as an optional global setting. The
legacy one-spec-for-all-days shape can be migrated where present; most client sites will leave
the option unused. Schema output must not require hours data.

## 2. Multi-location (omni `schema_add_locations` repeater)

omni's `schema_add_locations` repeater (sub-fields: `location_name`, `phone`, `street`, `city`,
`state`, `postal_code`, `country`, `latitude`, `longitude`, `map_url`, `areas_served`) emits each
row as a `"department"` array entry on the root `LegalService` node (see
`omni-leg-serv-schema-plugin.php` lines ~149–206).

**RESOLVED — see `CLAUDE.md` § Resolved Decisions.** Checking real client data (Ceja: 2 offices
on one shared directions page; Whitley Law Firm: 7-8 offices on one shared Contact page, no
dedicated per-location pages on either) ruled out a page-owned Location-per-page model as the
default — no current client's content architecture supports it. The chosen structure keeps omni's
options-page-repeater shape (closest to what's already there, least re-entry) but fixes what omni
got wrong:
- Each row gets a **computed `@id`** (`{contact_page URL}#location-{sanitize_title(name)}`, or
  `{location_page permalink}#legalservice` if a client ever does build a dedicated page) instead
  of omni's identity-less inline `department` object — this is what unblocks `provider` on combo
  pages, which omni's model couldn't support at all.
- Each location's node uses `"branchOf"` back to the global `Organization`, not a `department`
  array nested inside the org node.
- JSON-LD emission goes through `wp_json_encode()`, not string concatenation (fixes the escaping
  issues flagged in the original plugin review).

The `schema_add_locations` sub-fields (`location_name`, `phone`, `street`/`city`/`state`/
`postal_code`/`country`, `latitude`/`longitude`, `map_url`, `areas_served`) map close to 1:1 onto
the new `locations` repeater's sub-fields — this migration should be closer to a field-name
rename than a data-model rebuild.

## 3. Author / Person (omni `om_author` CPT + `_ac` fields)

omni registers a dedicated `om_author` CPT (non-public archive, `supports: title, editor,
revisions, custom-fields`) with fields `first_name_ac`, `last_name_ac`, `email_ac`, `phone_ac`,
`title_ac`, `image_ac`, `profile_page_ac`, `postal_address_ac` (group), `education_ac`,
`memberships_ac`, `awards_ac`, `links_ac`.

| omni field | schema.org property | nanato-seo target |
|---|---|---|
| `first_name_ac` + `last_name_ac` | `Person.name`/`givenName`/`familyName` | Team Member Layer 2 field group — direct match to the existing `Person` page type in `schema-markup-mapping.md` §Team member |
| `email_ac` / `phone_ac` | `Person.email`/`telephone` | Team Member field group |
| `title_ac` | `Person.jobTitle` | Team Member field group — matches `CLAUDE.md`'s explicit note that `jobTitle` (not schema type) distinguishes attorney/paralegal/staff |
| `image_ac` | `Person.image` | Team Member field group |
| `profile_page_ac` | `Person.url` | Computed from `get_permalink()` per two-tier `@id` rule, **not** a stored field — omni stores this manually; nanato-seo should derive it instead |
| `postal_address_ac` | `Person.address` (via `worksFor`) | Resolve from the global Organization address unless the person has a distinct office — omni always duplicates the firm address per author, which is redundant once Layer 1 exists |
| `memberships_ac` / `awards_ac` / `links_ac` | `memberOf` / `award` / `sameAs` | Team Member field group repeaters — **omni stores these as raw pre-formatted strings** (e.g. `$memberships` is echoed directly as a JSON array literal, meaning the ACF field itself likely contains hand-written JSON fragments) — confirm the actual field type in ACF before assuming a clean repeater migration; this may need reauthoring as real repeater rows, not a string transplant |

**Gap, not just a mapping question:** omni's author feature does double duty — it emits
`ProfilePage`/`Person` JSON-LD **and** renders a visible "About the Author" HTML chip
(`author_chip_insert()`, hooked to `the_content`). nanato-seo's stated scope (`CLAUDE.md`) is
schema/JSON-LD, explicitly *not* a content-rendering concern. Decide whether the visible author
byline component migrates too (as a theme template part, likely out of `nanato-seo`'s scope per
its own "flag content drift" rule) or is intentionally dropped/left to the theme.

## 4. Reviews / AggregateRating

omni pulls from a `reviews` CPT (not ACF-defined in this export — registered elsewhere, likely
the theme or another plugin) with per-post fields `review-author` and `review_rating`, and
computes `aggregateRating` by averaging all published `reviews` posts at render time on every
page load.

This aligns with the **deferred Reviews component** in `acf-field-model.md` Layer 3. The first
migration will not select a Reviews source or carry over omni's uncached live average. A later
implementation must choose between a manual source and an API/feed source, with caching required
for external or aggregate data.

## 5. Blog / Article

omni's `BlogPosting` block (`enable_blogposting` gate) is a near-direct match to
`schema-markup-mapping.md` §4 Single post view — `headline`, `author`, `description`,
`datePublished`/`dateModified` (both set to `get_the_time('c')`, i.e. **omni never distinguishes
published vs. modified date** — a known-lossy default worth deliberately deciding whether to keep
or fix), `publisher` with nested `logo`, and `image` from the featured image. No gap here beyond
that dateModified bug — straightforward migration.

---

## Summary of decisions still needed before implementation

1. ~~Opening-hours model~~ — **resolved**: retain optional support and migrate the legacy shape where present.
2. ~~Multi-location entity structure~~ — **resolved**, see `CLAUDE.md` § Resolved Decisions and §2 above.
3. Author-chip rendering remains optional and may be implemented separately from structured author data.
4. Confirm actual ACF field type behind `memberships_ac`/`awards_ac`/`links_ac` before assuming a clean repeater-to-repeater migration.
5. Reviews component approach (manual source vs. API/feed) remains deferred; do not carry over the uncached live-average query.
6. Decide whether to fix the `datePublished`/`dateModified` conflation when replicating `BlogPosting`.
