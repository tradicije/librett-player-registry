# API and portable JSON — proposal

[Srpski](../sr/PROTOCOL.md)

Nothing in this document is a shipped API. Names and fields are proposed; approve a normative schema and interoperability fixtures before implementation.

The [snapshot contract — draft 1](SNAPSHOT_CONTRACT.md) proposes concrete public types, limits, private/public revision separation and staged import identity rules. It refines the conceptual discussion below; signed feed and private restore contracts remain open.

## Transport and discovery

A WordPress adapter may expose `librett-registry/v1` under its REST API. The portable contract describes operations, not a mandatory `/wp-json/` URL. A connection URL/file advertises explicit endpoint addresses, registry UUID, display name, schema/protocol version, publication scope and trust metadata when enabled.

Entering a registry UUID alone cannot locate a source or prove its identity. First connection requires an authenticated out-of-band fingerprint/bundle, or explicit acceptance of first-use trust with its limitations. HTTPS secures transport but does not establish which independent organization owns a copied registry UUID. Never silently replace a pinned trust root from a newly fetched manifest.

Proposed operations:

| Operation | Consumers | Authorization |
| --- | --- | --- |
| Manifest/capabilities | Site/desktop | Public or catalogue read policy |
| Search/list player and club projections | Public/desktop | Publication scope |
| Consistent full snapshot | Replica/desktop | Dataset read/replication policy |
| Change page after cursor | Replica/desktop | Same policy; verified authority when enabled |
| Fetch public media descriptor/content | Clients | Media publication scope |
| Submit/query proposal | Paired replica | Scoped proposal credential |
| Moderate/CRUD/admin backup | Administrators | Server-side capability checks |
| Import signed authority claim | Recovery/admin | Existing pinned recovery trust + local admin |

Desktop is a read/import consumer. It never calls proposal or CRUD operations. API namespaces can be identical across independent sites without making their registries identical.

All public read operations use the same approved allowlisted projection described in the proposed [publication policy](PUBLICATION_POLICY.md), including nested memberships and media. Private drafts, authorization evidence and private edit counters are excluded. Public catalogue reads are proposed for the first milestone; private catalogue authentication is deferred. Unsigned file import stages unpublished records rather than trusting imported publication flags. Administrative restore and verified replica application need separate contracts.

## Snapshot envelope

Proposed logical fields: `format`, `schema_version`, `registry_id`, `authority_generation`, `checkpoint`, `exported_at`, `publication_policy`, `players`, `clubs`, `memberships`, `media`, `tombstones`, plus a detached/authenticated publication envelope when signed trust is enabled. Timestamps are display metadata, not sole freshness/order evidence. SQL primary keys, passwords, private audit, proposal evidence and recovery secrets are excluded.

Snapshots are internally consistent at one checkpoint. Optional/missing fields and explicit null have defined distinct meanings; absent public fields cannot be interpreted as authority to erase local desktop data. Schema review must fix size/count/depth/string/media limits and required vs optional fields.

Unsigned file import may be allowed as an explicitly unverified local import; it cannot establish trusted primary identity. A signed import must validate its supported envelope, canonical bytes, authorizing chain and schema before applying data.

## Change feed

Each accepted change carries registry identity, authority generation, strictly ordered sequence, stable change ID, entity type/UUID, expected/new revision and upsert or minimal tombstone. A signed page/checkpoint binds its ordered contents and authority context; JSON serialization alone is not a signature specification.

Use a stable upper checkpoint for a sync session. Pagination cannot drift as new writes occur. Apply each validated page and its cursor in the same transaction, retain the last accepted cursor on failure, and make replay idempotent. Missing links or expired feed retention require a consistent snapshot resync; never skip unknown gaps.

Generation changes need explicit reconciliation with a declared restored checkpoint. Sequence alone cannot select a winner after recovery. A new primary must not reuse the old publication key or conflicting change identifiers after rollback.

## Signing proposal

Evaluate a maintained Ed25519 implementation and a specified canonical JSON representation (for example RFC 8785). Define an envelope context/version and sign the registry ID, generation, object kind and complete canonical payload. Fix Unicode, number, null/missing-field and array-order rules. This is a protocol design task, not approval to handwrite cryptography.

Sources: [EdDSA / RFC 8032](https://www.rfc-editor.org/rfc/rfc8032), [JSON canonicalization / RFC 8785](https://www.rfc-editor.org/rfc/rfc8785).

## Proposals

A request contains peer/request UUID, registry ID, operation, target UUID or requested new profile, base entity revision and validated requested values. Store a pending proposal separately from published records. Retries with the same request ID/payload return the same outcome; ID reuse with different payload is rejected. Approval checks the current revision and commits one authoritative mutation. Stale proposals need human review; rejection produces no player revision.

## Validation and errors

Bound downloads and parsing before allocating full content. Validate references, UUIDs, duplicate IDs, enums, revisions and media digests. Stage large imports and switch atomically; partial profiles are not publicly served as a complete snapshot. Preserve accepted state on malformed or unsupported input.

Stable error codes distinguish authentication, permission, conflict, unsupported schema, untrusted authority, stale checkpoint, invalid data and resource limits. UI translates messages; error codes remain language-independent. Numeric HTTP details are finalized with the API schema.

## Portability and permission

The same logical snapshot is available as a file and HTTP download. URLs can change; identity/trust remain pinned. Mirrors serve only validated public projections and retain source/checkpoint information. Dataset publication/redistribution permissions are separate from AGPL; the manifest must describe allowed public scope and mirroring policy.
