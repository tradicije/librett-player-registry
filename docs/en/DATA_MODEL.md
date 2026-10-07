# Data model and lifecycle

[Srpski](../sr/DATA_MODEL.md)

Status: conceptual model, not a finalized migration/schema.

## Identity

A registry UUID is independent of its URL and paired with pinned trust metadata when authenticated replication is introduced. Players/clubs have UUIDs unique within the registry. External identity is (registry UUID, entity UUID); WordPress numeric keys and public slugs are not identifiers. Same names never imply same people; different registry identities never silently merge.

An imported replica preserves registry/entity IDs. A newly created independent registry receives a new registry ID. A divergent fork must announce a new identity and optional provenance; it cannot claim the original primary solely because it copied records.

## Conceptual custom tables

| Logical collection | Purpose |
| --- | --- |
| Registry metadata | Name, UUID, schema/protocol version, publication policy |
| Players | UUID, revision, profile fields, publication state |
| Clubs | UUID, revision, name, abbreviations/aliases, status |
| Memberships | Player/club relation; history requirements to be finalized |
| Media | Stable ID, content digest, type/size, rights/publication metadata |
| Accepted changes | Ordered public projection changes/checkpoints |
| Authority claims | Accepted generation, publication keys and signed transitions |
| Replica state | Peer identity, cursor, source/freshness and synchronization status |
| Proposals | Request identity, base revision, requested patch, moderation status |
| Private audit | Actors and administrative decisions, not public replication |

Use local SQL indexes/constraints and transactions through repositories; finalize details in schema review. Avoid duplicating a fact in several mutable JSON blobs. Portable export is a versioned model, not a SQL dump.

The proposed [publication policy](PUBLICATION_POLICY.md) defines the public allowlist and separates editable records from approved projections. New/file-imported records start unpublished; private edits require publication review. Public revision/checkpoint metadata must not reveal private edit counts. Exact storage and JSON schemas remain open.

## Player profiles

First candidate fields: display/given/family name where provided, optional birth year, country/region, club links, plain-text biography and photo reference. Decide which fields are public before collecting real data. Detailed achievements and historical memberships need explicit models rather than unvalidated arbitrary JSON.

Do not publish exact birth dates, contact details, identity documents or minor profiles by default. Birth year may be absent online even though Desktop requires one when creating a valid local profile; the importer needs a completion workflow. Slugs can change without changing UUIDs.

## Revisions and deletion

Primary mutations check expected entity revision and commit record, audit and public change projection atomically. Revision conflicts require reload/review. Global feed sequence is scoped to authority generation; recovered older checkpoints must not reissue the same logical change identifier with new contents.

Archive hides or marks records according to publication policy. Withdrawal removes the public projection and emits a minimal tombstone. A purge removes personal fields/media according to retention policy; deletion events must not carry deleted PII. A replica applies removal, while Desktop marks upstream withdrawal without deleting tournament history.

The registry cannot force every previously downloaded public copy to disappear. Retention and legal erasure obligations require separate organizational decisions. A stale import must not resurrect a tombstoned record silently.

## Data independence

Public JSON snapshots contain only published data, schema identity and validated trust/provenance when enabled. Private backup contains administrative data; media bytes need a separate portable package or verified download plan. A backup must not include the offline recovery secret.

Activation ships zero player/club records. On setup/import, records live in the installation's database outside the plugin source. Plugin updates replace code, not the registry. Schema updates require pre-migration backup and an explicit safe-failure policy.
