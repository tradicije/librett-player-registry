# Trust, replicas, proposals and recovery

[Srpski](../sr/TRUST_AND_RECOVERY.md)

Status: threat-model and protocol requirements; not implemented or audited.

## Identity and keys

Registry UUID identifies a dataset, not its owner. Clients pin a recovery/public trust identity after verified bootstrap. Keep two authorities separate:

- Offline recovery authority: authorizes operational key/primary endpoint changes; secret held by maintainer outside normal servers and replicas.
- Operational publication key: primary signs normal accepted snapshots/changes; no ability to grant itself a new recovery root.

Authenticated authority claims bind registry ID, parent claim, generation, primary endpoint, new publication key and data checkpoint. A complete canonical/signature specification is required. The “recovery code” UI is not an ordinary password accepted by any server that knows a hash.

## Primary/replica behavior

Only primary-authorized mutations generate accepted shared revisions. Replica admin controls local presentation, access settings and proposal submissions. Shared CRUD rejects writes in replica mode at the use-case/API layer.

A hosting owner can modify the open-source plugin or local database. Cooperating clients refuse unapproved shared publications based on signatures and pinned authority. The system cannot prevent arbitrary local forks or claim that a physical host cannot edit files.

Initial trust from an arbitrary manifest is not independently authenticated. Show registry name, host and fingerprint; support a trusted connection bundle/out-of-band verification. TOFU, if allowed, is explicit. Key changes require an accepted transition, not a freshly fetched public key alone.

## Proposals

Pair a replica actor separately from public read access. Grant revocable credentials scoped to submitting/querying its requests, not publication or registry administration. Apply rate limits and server permissions. Pending proposals and private evidence are not public profile data. Primary acceptance checks base revisions and records actor/outcome; stale requests cannot silently overwrite a newer profile.

## Recovery workflow

1. Obtain the newest available data backup or verified replica checkpoint; a key cannot recreate lost records.
2. On the destination host, restore/stage data and generate a fresh operational key.
3. Outside the destination server, the maintainer signs a claim authorizing that key/endpoint and declaring the restored checkpoint under a new authority generation. Exact offline signing UX is still open; never upload the root private key to an arbitrary replacement host.
4. Destination verifies the claim against pinned recovery authority, applies a transaction and enables primary operations for an authenticated administrator.
5. Distribute the signed claim via existing peers, a connection file or direct operator contact. Clients verify it and reconcile data before changing sources.

The old site is not required. A signature does not automatically tell offline replicas where the new host is; discovery remains a transport/operator workflow.

## Returning old host and conflicts

Clients retain accepted authority generation and chain/checkpoint. After accepting a valid handover, old operational publications cannot advance that registry. A disconnected client that has never received the claim may still see the old primary; show accepted authority and freshness and provide manual import/update.

There is no central coordinator in this design. Two holders of a copied recovery secret can issue conflicting claims. Detect identical-generation divergent claims and pause shared synchronization for explicit resolution; do not pick whichever arrives first as a universal truth or accept an unchecked larger number. Even a valid higher claim needs chain rules and a declared data reconciliation policy.

Define recovery-key rotation before shipping, including how long-offline clients establish continuity. Lost recovery key means no authenticated takeover under the existing trust identity unless a previously configured alternative authority exists; an independent fork/new registry is a different identity.

## Data recovery and rollback

Restore must declare exactly which checkpoint is available and whether recent data may be missing. Do not reuse the old publication key or old event identifiers for conflicting new contents. Generation/revision and snapshot-resync semantics must be reviewed with adversarial fixtures. Public replicas may not have all private admin/media data needed for full organizational restoration.

## Required security review

Operational compromise, stolen recovery secret, malicious source/mirror, tampered JSON/media, duplicate/reordered feeds, replay, exhausted resources, SSRF/redirects, stale roots, concurrent takeover, interrupted migration and private-data exposure. Pin maintained crypto/HTTP libraries and audit the trust ceremony; this document itself is not a security guarantee.
