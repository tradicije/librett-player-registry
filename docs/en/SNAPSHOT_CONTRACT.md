# Portable snapshot contract — draft 1

[Srpski](../sr/SNAPSHOT_CONTRACT.md)

Status: proposed Phase 0 contract for review, not a shipped format or approved normative JSON Schema. `schema_version: 1` is a candidate format identifier, not a plugin release. This draft specifies a full public snapshot for the single-site milestone; it does not specify signed replication, private backup or administrative restore.

## Types and envelope

UTF-8 JSON with one object root, no duplicate object keys, trailing content or non-finite numbers. Reject unknown properties at every object level; extensions require a supported schema version. No implicit coercion. UUIDs are lowercase canonical hyphenated non-nil UUID strings; generated IDs are random UUIDv4 through an injected maintained generator. Existing imported UUIDs are validated and preserved as provenance, not regenerated from names or URLs.

Counters are canonical decimal strings (`0` or a nonzero digit followed by digits), at most 19 digits and no greater than 9223372036854775807. Compare numerically, never lexically; this avoids JavaScript number precision loss. Public entity revisions start at `"1"`; snapshot checkpoint starts at `"0"`. Counter exhaustion fails explicitly without wrapping. Strings must be valid Unicode; limits below count Unicode code points except byte limits.

Every envelope property in this table is required:

| Property | Value |
| --- | --- |
| `format` | Exact string `librett-registry-public-snapshot` |
| `schema_version` | Integer `1` |
| `registry_id` | UUID of the originating registry |
| `registry_name` | Nonblank plain text, maximum 200 characters |
| `authority_generation` | `null` for this unsigned draft; a non-null value requires a future separately supported authenticated contract |
| `checkpoint` | Public publication sequence counter |
| `exported_at` | UTC timestamp in exact `YYYY-MM-DDTHH:mm:ssZ` form with a valid calendar date; display metadata only |
| `publication_policy` | Object described below |
| `players`, `clubs`, `memberships`, `media`, `tombstones` | Arrays; empty arrays allowed, never `null` |

`publication_policy` contains exactly `version` (nonblank string, maximum 64), `purpose` (nonblank plain text, maximum 1000), `dataset_terms_url` and `media_terms_url` (HTTPS URLs, maximum 2048), and `distribution_scope` (exact string `public-download`). These are operator declarations, not machine proof of permission or legal compliance. Export is disabled until terms and publication approvals are configured. URLs are metadata; parsing/import never fetches them automatically. Private approval evidence and private counters are forbidden.

## Published records

Required fields cannot be omitted or null. Optional fields may be omitted; null is rejected in this draft. Omission means not publicly supplied and does not authorize clearing a Desktop local value. Empty optional text and empty references are rejected; an empty `aliases` array is allowed. The snapshot contains full projections, never patches.

| Object | Required fields | Optional fields |
| --- | --- | --- |
| Player | `id` UUID, `revision` counter, `slug`, `display_name` | `given_name`, `family_name`, `birth_year`, `country`, `region`, `biography`, `photo_id` UUID |
| Club | `id` UUID, `revision` counter, `slug`, `name` | `abbreviation`, `aliases` array, `country`, `region` |
| Membership | `player_id` UUID, `club_id` UUID | None |
| Media | `id` UUID, `revision` counter, `content_sha256`, `mime_type`, `byte_length`, `width`, `height`, `content_url`, `attribution` | None |
| Tombstone | `entity_type`, `entity_id` UUID, `revision` counter, `removed_at_checkpoint` counter | None |

Names/display names/aliases are nonblank plain text up to 200 characters; club abbreviation up to 32; country/region up to 100; biography up to 5000. Plain text rejects control characters except line feed in biography; rendering always escapes text. No inferred name splitting or locale-based identity matching. Country/region are descriptive labels, not claims of a standardized country-code schema.

Slug: 1–200 lowercase ASCII letters/digits separated by single hyphens, no leading/trailing hyphen. Slugs are unique within an entity type among currently published records and may change without changing identity. Names need not be unique. `birth_year` is an integer from 1 to 9999; publication review decides plausibility and minor status, without relying on a clock inside domain rules. Desktop may impose stricter validation or require completion.

Memberships describe current published links, no membership history or primary-club semantics. The `(player_id, club_id)` pair is unique and both records must be included. Player/club IDs are unique within their respective types. Media IDs are unique within media. References are resolved by type, not by name.

Media describes the public validated image derivative, not an original or attachment ID. `content_sha256` is exactly 64 lowercase hexadecimal characters. `mime_type` is `image/jpeg` or `image/png`. Byte length is an integer from 1 to 5242880; width/height integers from 1 to 4096, with at most 16777216 decoded pixels. `content_url` is an absolute HTTPS URL up to 2048 characters, with no userinfo or fragment. `attribution` is nonblank plain text up to 500 characters. Every photo reference resolves to included media; every media descriptor is referenced by a published player. No image bytes are embedded. Importing a descriptor never fetches it automatically; an authorized later download verifies network policy, digest, actual type, dimensions and decode limits. A URL change requires a new public media revision.

`entity_type` in tombstones is `player`, `club` or `media`. A tombstone contains no name, deletion reason, actor or other personal field. Its checkpoint is positive and no greater than the snapshot checkpoint. No `(entity_type, entity_id)` may appear both live and tombstoned. Keep the latest tombstone for each withdrawn identity; republication explicitly replaces it with a newer live revision. A snapshot never includes a tombstone for a never-published draft. Tombstone compaction and feed retention are deferred; never silently drop removal knowledge because of resource limits.

## Ordering and limits

Export arrays are sorted by UUID (`players`, `clubs`, `media`), by player then club UUID (`memberships`), and by entity type then UUID (`tombstones`). Consumers must accept other array orders; this ordering does not define cryptographic canonicalization. Object property order has no meaning.

Proposed hard import/export limits: 32 MiB UTF-8 bytes, nesting depth 8 (root object depth 1; every nested array/object adds 1), 100000 aggregate array items across all arrays, 20000 players, 5000 clubs, 40000 memberships, 20000 media and 50000 tombstones. Aggregate and per-array limits both apply, including aliases; at most 20 unique aliases per club. Individual JSON string tokens are bounded to 32768 encoded bytes as well as field character limits. Compressed files/embedded archives are unsupported. Limits are a review baseline, not measured capacity claims; operators may lower them, never bypass them. Export that exceeds limits fails without producing an incomplete snapshot; a larger/paged format needs a new reviewed contract.

## Revisions and transactions

Private `edit_revision` is separate from public `revision`. Saving a draft increments only the private revision and audit. Publishing checks expected private revision, current public revision and policy version; a successful public change increments the entity public revision and global checkpoint, and atomically commits projection, removal/upsert events and private audit. No-op public approval does not create a public revision or checkpoint. First publication starts public revision 1; withdrawal and explicit republication each advance it, never resetting it after purge. Purged UUIDs are not assigned to a new person.

A transaction affecting several public entities assigns a distinct consecutive sequence to each changed projection; one snapshot captures the final committed checkpoint. Changes to memberships advance the corresponding player projection revision; club deletion/withdrawal also removes affected public memberships and advances affected players. Media withdrawal removes photo references and advances affected players atomically. If the full dependency update fails, no partial public state is committed. Private edits cannot determine public sequence values.

A publication-policy metadata change advances the public checkpoint even if no entity fields change; narrowing scope also updates affected projections atomically. Private publication evidence changes do not advance it. Read/export uses a consistent database view of projections, policy and checkpoint. Internal event/schema layout and future signed feed envelopes remain open.

## Import modes and identity

1. **Create independent primary from file:** allocate a new registry UUID and fresh local entity UUIDs; retain explicit source-to-local mappings/provenance and rewrite relationships. Stage all imported content as unpublished drafts. Do not copy source counters as local counters or accept a file's authority metadata as local authority.
2. **Merge file into existing primary:** preserve the local registry identity, stage candidate changes and require explicit mappings; previously imported IDs use stored mappings. Equal names never auto-merge. Existing published projections remain unchanged until reviewed approval. Repeated identical input/mappings creates no duplicate drafts or audit mutations; newer source data requires review against expected local edit revisions. Unsigned revisions do not prove freshness.
3. **Desktop read/import (later milestone):** retain remote identity and local mapping separately, preserve explicit local overrides and historical snapshots, preview additions/changes, and treat omitted fields as unavailable. Import alone never authenticates a source.

Private restore preserving registry identity is a different workflow and cannot be reconstructed from this public snapshot. Verified replica application is unavailable in this draft. A copied unsigned file cannot establish primary authority or overwrite a pinned trusted source. URL migration does not change remote identity.

Bound input before parsing; validate the entire graph and limits into staging; preview counts, provenance, conflicts and requested mappings; recheck permissions, expected revisions and policy at confirmation; commit all accepted draft updates and import receipt atomically. Failure/cancel retains existing data and import state. Receipts use a local request UUID and payload digest: identical retry returns the original result; reusing a request UUID with different bytes fails. Independent semantic repeat detection uses validated normalized content and mappings, not timestamp or object key order. Preview expires on relevant local changes and must be regenerated. File parsing never executes HTML, follows URLs or uploads data.

## Review examples and remaining work

An empty registry has checkpoint `"0"`, empty arrays and configured policy. First player publication has revision `"1"` and checkpoint `"1"`; a private edit changes neither. Withdrawal then yields no live player and a player tombstone at revision `"2"`, checkpoint `"2"`. A subsequent approved republication uses revision `"3"` and replaces the tombstone. Remote revisions on an unsigned file remain unverified metadata.

Required review cases include duplicate JSON keys/UUIDs, dangling references, null optional fields, unknown fields, invalid UTF-8, limits exceeded, large counters, duplicate memberships, forbidden private data, same names/different IDs, repeated import, changed payload under the same request UUID, stale preview, interrupted commit, dependency withdrawal and offline Desktop preservation. These are proposed cases, not tests or interoperable fixtures already executed.

Next: approve this draft, author normative JSON Schema and synthetic interoperability fixtures, specify private storage/migration rollback, confirm bounded parser/tooling and deployment matrix, and review authenticated envelopes separately. No runtime compatibility or security verification is claimed.
