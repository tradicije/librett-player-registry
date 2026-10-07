# Schema and interoperability examples

[Srpski](../sr/CONTRACT_VALIDATION.md)

Status: Phase 0 engineering baseline; schema and examples are documentation artifacts, not an implemented importer. English identifiers are canonical. See the [snapshot contract](SNAPSHOT_CONTRACT.md).

## Artifacts and validation layers

The local [unsigned snapshot schema](../contracts/schemas/public-snapshot-v1.schema.json) uses [JSON Schema 2020-12](https://json-schema.org/specification). Resolve only bundled local schema references; never fetch remote schemas from an input document. The schema URI is an identifier, not a discovery address.

Validation is ordered: bounded byte/token parsing → schema → semantic graph/context checks → permission/mapping preview → atomic commit. A schema-valid document is not automatically safe, authorized, fresh or authentic.

| Layer | Mandatory responsibility |
| --- | --- |
| Transport/parser | 32 MiB limit before allocation, decompressed byte limit if HTTP content encoding is later supported, depth 8, string token 32768 encoded bytes, aggregate array count 100000, UTF-8 validity, duplicate object-key rejection, complete document, no coerced types |
| Schema | Required/unknown fields, structural types, text and field limits, UUID shape, finite integer ranges, decimal counters including maximum, optional-field null rejection, individual array limits |
| Semantic | Calendar date, URL authority/userinfo/fragment and HTTPS rules, UUID acceptance, unique typed IDs/slugs, references, duplicate membership pairs, media reachability, live/tombstone disjointness, checkpoint ordering, policy scope |
| Context | Publication approval, minor policy, expected private/public revisions, local identity mappings, stale/changed preview, receipts, trusted-source rules, old accepted removal knowledge |
| Media/network | Deferred explicit download only; SSRF/redirect/DNS defenses, size/time/type/digest/decode limits and rights |

2020-12 `format` may be annotation-only. Validators must explicitly assert `date-time`/`uri`, with application checks for exact UTC spelling, valid Gregorian calendar (no leap second in this format), URL host/port syntax and no userinfo or fragment. Schema regexes do not replace URI parsing. UUID shape permits RFC-layout versions 1–8 with the RFC variant; generated IDs use v4. Reject nil and malformed IDs. Text rejects C0/C1 controls and DEL, except LF in biography; nonblank means at least one non-whitespace character. Preserve valid names as entered; schema validation never performs an identity merge.

Unique array items detect identical objects only, not duplicate IDs in otherwise different objects. Application checks enforce uniqueness by typed identity and slug, including membership pairs. Counters compare as exact integers. Every live revision is at most the checkpoint; every tombstone revision is at most its removal checkpoint; a nonempty live/tombstone set requires a positive checkpoint. The private revision is never serialized. Omitted optional fields mean unavailable; explicit null is unsupported in the public v1 shape. Policy and snapshot arrays must come from one committed view.

## Synthetic examples

All examples use invented names and `example.invalid`. Policy URLs and image descriptor/digest are placeholders, not real licenses, image bytes or permission evidence. They are never fetched. No player database or real photograph is included. The contract artifacts inherit the project AGPL-3.0-or-later license.

| Document | Expected review result |
| --- | --- |
| [empty](../contracts/examples/empty.json) | Accept structurally and semantically as an empty configured catalogue, checkpoint 0 |
| [published](../contracts/examples/published.json) | Two distinct players with the same display name; one club, membership and synthetic media descriptor; second player intentionally has no birth year |
| [withdrawn](../contracts/examples/withdrawn.json) | Minimal player tombstone, no live player or personal fields |
| [large counter](../contracts/examples/large-counter.json) | Preserve 9007199254740993 exactly as a string |
| [null optional field](../contracts/examples/reject-null.json) | Reject at schema: birth year cannot be null |
| [private field](../contracts/examples/reject-private-field.json) | Reject at schema: private note is unknown/forbidden |
| [counter overflow](../contracts/examples/reject-counter-overflow.json) | Reject at schema: exceeds signed 64-bit maximum |
| [duplicate key](../contracts/examples/reject-duplicate-key.json) | Reject at parser before schema; never use last-key-wins parsing |
| [duplicate ID](../contracts/examples/reject-duplicate-id.json) | Reject at semantic layer, although the player objects differ |
| [dangling reference](../contracts/examples/reject-dangling-reference.json) | Reject at semantic layer: club does not exist |
| [future tombstone](../contracts/examples/reject-future-tombstone.json) | Reject at semantic layer: removal is beyond snapshot checkpoint |

These are expected outcomes, not executed implementation tests. Oversize/depth, malformed encoding, migration interruption and authorization cases are documented in the [readiness record](PHASE_0_READINESS.md); they need implementation-time tests rather than committing large hostile files. No validation command is advertised until a validator and configuration are installed with authorization.
