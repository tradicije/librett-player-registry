# Authenticated publication and authority transfer — design review

[Srpski](../sr/AUTHENTICATED_PUBLICATION.md)

Status: Phase 0 threat-model baseline for later phases, not an approved wire format, implemented cryptography or security audit. The unsigned v1 snapshot schema remains unchanged and cannot carry trusted authority. Authenticated envelopes require a distinct format/schema and fixtures before Phase 3; authority transfer remains Phase 5.

## Candidate byte contract

Use maintained Ed25519 through an injected sodium adapter and [RFC 8785 JCS](https://www.rfc-editor.org/rfc/rfc8785) through an independently reviewed maintained canonicalizer. No handwritten algorithm or canonicalizer. Signed JSON is restricted to I-JSON, unique keys, valid Unicode and safe-range integers; revision/generation/sequence remain decimal strings. Preserve Unicode exactly, without normalization. Array ordering is defined by the signed payload; verification never sorts or repairs it.

Candidate envelope: exact `format`, `envelope_version`, `algorithm` (`Ed25519`), `key_id`, `payload`, `signature`. Signature uses unpadded base64url of 64 bytes; key IDs are lowercase SHA-256 of the raw 32-byte public key. Verify the signature over JCS bytes of the entire envelope object excluding only `signature`, binding format/version/algorithm/key ID and payload. Reject unknown envelope fields/algorithms; keys are resolved from pinned authority, never accepted from the envelope itself. Parsing/schema/resource validation precedes crypto; successful crypto is followed by semantic and contextual validation. These names are candidates for the later normative schema, not fields to add to unsigned v1.

Publication payload must bind registry UUID, accepted claim digest, authority generation, object kind, checkpoint/cursor context and full snapshot or ordered change page. A page binds lower-exclusive/upper-inclusive sequence, fixed session upper checkpoint, previous page digest and contents. Changes include stable UUID, type/UUID, expected/new public revision and full upsert/minimal tombstone. Policy-only events have a distinct kind and do not invent an entity revision. Never sign private drafts or evidence. Limits are no weaker than unsigned transport; keys/claims have a separate small bounded limit (64 KiB per claim, chain depth 64) and oversized chains require a reviewed checkpoint bundle, not truncated verification.

## Bootstrap and claim chain

A trusted connection bundle contains registry UUID, recovery public key/fingerprint, initial signed authority claim and explicit endpoints. Authenticate the fingerprint out of band or explicitly mark first-use trust unverified. HTTPS alone does not authenticate organizational authority. Never replace an existing root from a fresh manifest. An initial claim uses generation 1, no parent, operational public key, endpoint and initial data checkpoint, signed by the offline recovery authority.

Subsequent claims bind registry UUID, parent claim digest, exactly parent generation +1, fresh operational public key, primary endpoint, restored data checkpoint/digest and reconciliation declaration. Digest refers to complete canonical authenticated envelope bytes. Verify the entire continuity path from the accepted root/claim before any change. A signed larger generation without the accepted parent path is not sufficient. Endpoint fetching applies the same bounded network policy and does not make the hostname part of identity.

Operational key rotation is authorized by a recovery-signed next-generation claim; ordinary server keys cannot grant a new root. Root rotation requires a transition binding old/new public roots and parent claim, signed by both old and new offline roots. Long-offline clients verify retained continuity bundles. Alternative recovery authorities are not configured in the first design. Lost sole recovery secret means no authenticated continuation under the existing identity; an independent fork receives a new registry UUID.

## Replay, conflicts and restored data

Persist accepted root/claim/generation, checkpoint, page digest and removal knowledge in the same transaction as accepted projections/cursor. Reject old generations, rollback, sequence gaps, duplicate IDs with different bytes, and same-generation divergent claims. Duplicate exact bytes are idempotent. A valid descendant cannot conceal a conflicting sibling: record the conflict and stop synchronization for explicit resolution; neither timestamp, endpoint nor unchecked generation selects a winner.

A new client without a trusted recent checkpoint cannot prove freshness from signatures alone; show that limitation and accepted source/checkpoint. Polling failures keep the last verified projection with a stale label. No automatic host discovery or leader election is promised.

Recovery restores data separately from authority. A claim declares the source checkpoint/digest and possible loss; operator review reconciles against each client's accepted checkpoint. When the restored data is behind, use a full authenticated resync plus explicit loss report; never silently lower revision/removal knowledge or resurrect withdrawn profiles. Retain the union of known removal suppressions until a newer explicitly approved publication reconciles them. Unavailable previously live data is marked unavailable during resync, without inventing deletions or changing Desktop history. New-generation revisions are scoped to that generation and old event IDs are never reissued with conflicting bytes. Clients with divergent accepted state may require explicit operator reconciliation before switching, even with a valid claim.

Old hosts cannot advance a client after it accepts the handover. Disconnected clients unaware of it may continue seeing the old authority; expose accepted claim and manual bundle import. Public replicas cannot reconstruct lost private audit, approvals or missing original media. Never place a recovery private key in a host, replica, public snapshot, log, repository or ordinary backup.

## Proposal and remote-input boundaries

Proposal clients are paired registry-site actors with revocable registry-scoped credentials, separate from public reads. Credentials authorize submit/query-own requests only, never publication. Check request UUID/payload binding, limits/rate limit and expected base revision; primary moderation commits a normal publication transaction. Private evidence stays private. Desktop never obtains a proposal credential or calls write operations.

Before connecting any remote source, reject private/loopback/link-local/reserved destinations, unsupported schemes/ports and userinfo; validate DNS and actual connection destination, TLS, redirects at every hop, timeout, content type, compressed/decompressed size and image decode limits. No automatic policy-URL fetch. A signed body remains untrusted parser/rendering input. Precise HTTP adapter limits and signed interoperability/security fixtures are phase-specific implementation gates.

## Mandatory review scenarios

Altered Unicode/number/array bytes, unknown algorithms, mismatched key ID/context, tampered page bounds, duplicate/reordered/gapped feed, replayed claim/page, substituted manifest root, compromised operational key, copied/stolen recovery root, sibling claims, valid descendant of wrong branch, returning old host, stale restore with newer client tombstones, lost root, long-offline root rotation and SSRF/redirect/decompression attacks. This documents the review and required cases; no security audit or crypto test has been performed.
