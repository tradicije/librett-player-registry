# Storage, transactions and migrations

[Srpski](../sr/STORAGE_AND_MIGRATIONS.md)

Migration 002 adds module-owned private players/clubs/audit tables, without modifying migration 001 definitions/checksum. It has one resumable additive step per table and verifies the complete structure before marking completion. Configured registries require operator backup acknowledgement through the settings capability/nonce form. This limited extension is unverified and is not a general upgrade/restore runner.

Phase 1 update: the [implemented bootstrap](IMPLEMENTATION_STATUS.md) records installed versions and actual tests. The remaining contracts below are planned; Phase 0 review statements refer to that historical documentation task.

Status: Phase 0 engineering baseline for Phase 1 implementation; the initial identity migration is implemented; later storage below is planned. One WordPress single-site registry, InnoDB tables and one shared transaction connection. Network activation/multisite and nontransactional engines are excluded from the first matrix.

## Ownership and logical keys

UUIDs are immutable logical keys; a SQL surrogate is adapter-local and never exported. Store UUIDs consistently as ASCII `CHAR(36)` with binary comparison, counters as nonnegative signed-range `BIGINT`, text as `utf8mb4` with appropriate bounded columns. Do not rely on name collation for identity. Table names use the local WordPress prefix and `librett_registry_` namespace; concrete DDL comes with implementation.

| Owner | Logical tables / constraints |
| --- | --- |
| RegistryIdentity | Singleton setup/registry UUID, role, private policy version and lifecycle; no automatic identity on activation |
| Players | Draft player fields, private edit revision, active/archived retention state; UUID unique; private publication authorization/evidence references |
| Clubs | Draft clubs, aliases and current draft memberships; club UUID unique, membership `(player_uuid, club_uuid)` unique |
| Media | Media UUID, private storage handles, digest/dimensions, rights, staged/available/removal state; attachment ID is optional private metadata |
| Publication | Approved player/club/media projections, approved memberships, public revisions/slug indexes, tombstones, ordered events and singleton checkpoint/policy projection |
| Application coordinator | Import jobs/staging/receipts and source mappings through dedicated ports; mapping `(source_registry, type, source_uuid)` unique within local registry; request UUID unique |
| Private audit port | Authorized actor/time/action and result, limited evidence references; never public payloads or credentials |
| Migration adapter | Schema version, migration ID/checksum, started/completed/failed steps and lock ownership |

Later modules add their own authority, replica/proposal tables only when implemented. A module cannot read another owner's tables. A publication projection is an immutable approved copy, not a second mutable working profile. Memberships live in Clubs; Publication owns only approved copies. Foreign-key/index enforcement is specified in DDL; repositories also validate references. No cascading deletion may remove audit/tombstone/import provenance silently.

## Transaction contract

All participating repositories and Publication ports use the same transaction manager/session. A successful use case commits once; exceptions roll back. No HTTP, file decoding or long-running media transfer holds SQL locks. Lock order: registry/checkpoint → typed entity UUID order → dependent membership/media/projection rows → receipts/audit. Deadlocks return a retryable failure; a bounded retry uses the same request ID and rechecks expected revisions.

Draft writes compare `edit_revision`; affected draft relationships advance the corresponding player's edit revision. Publication compares draft/public revision and policy version, checks use-case capabilities, then writes public projections, dependent removals, events, checkpoint and audit together. No-op publication adds no public event. Snapshot export uses a read-only consistent InnoDB view and finishes before handing bytes to the response; it cannot mix checkpoints across arrays. Export streams to bounded private staging when needed, never a public partial file.

The singleton public checkpoint serializes allocations. Each changed projection/event receives a consecutive sequence; a policy-only event also advances it. Failed allocations roll back without publishing gaps. Purge retains a nonpersonal identity/revision ledger and current tombstone; source mappings are redacted according to retention policy rather than left with personal payloads. No names in removal events. Approved club/media withdrawal updates all referring public player projections atomically.

Import staging is private and nonpublic, identified by job UUID, input digest, normalized-content digest, selected mode/mappings and expected local revisions/policy. Confirmation rechecks actor permission and state. Failed/cancelled/expired jobs cannot apply and retain only limited diagnostic metadata until cleanup. Receipts are committed with draft changes; retries do not duplicate effects. Source tombstones are review candidates, never automatic deletion of a local primary record. Private backups and logs exclude offline recovery secrets.

## Media and external side effects

Validate image bytes outside a transaction into private staging. A transaction reserves a media record; a retryable post-commit finalization makes validated content available. Publish only finalized media. If finalization fails, keep the record private and show an actionable failure. Orphan cleanup is explicit and references-aware. No public original/derivative URL exists before approval; withdrawal closes delivery access transactionally and purges physical bytes through a durable private cleanup queue. Failure to unlink cannot reopen access. The media adapter must account for hosting/CDN caches and cannot promise deletion of downloaded copies.

## Migration safety

Activation preflights the chosen runtime/extensions/engine and acquires an exclusive migration lock, then creates empty schema/setup only. A migration records immutable ID/checksum and target schema version. SQL DDL may implicitly commit: do not promise transaction rollback for schema changes. Prefer additive, resumable expand/backfill/verify/contract steps; record each durable step. Never advance completed schema version until all required constraints/data checks succeed.

Before an upgrade, require a verified operator backup of database plus protected media and configuration (excluding recovery secret), with a documented restore procedure. Disable mutations during migration; serve a compatible accepted public view only when it is known safe, otherwise return maintenance. Never show staged partial data. On interruption restart from the recorded step, validate existing objects/checksum and refuse ambiguous or mismatched state. Failure retains maintenance status with diagnostics stripped of PII/secrets.

Automatic destructive down-migrations are excluded. Restoring an old private backup is an explicit offline maintenance workflow that restores the matching code/schema/media set. For an unsigned single-site registry, resumed publication after an older checkpoint requires a new registry identity unless a later authenticated generation-transfer contract is available; never reuse old public IDs/counters for conflicting state. Public snapshots cannot restore drafts, approvals or private audit.

Deactivation leaves data and capabilities intact. Default uninstall preserves registry tables/media; deletion requires explicit authorized opt-in, backup/retention review and a dedicated purge operation. Activation after deactivation reuses the existing registry. Plugin updates replace code without importing sample records.

## Required implementation scenarios

Schema creation twice, empty activation, failed preflight, simultaneous setup, stale edit/public revision, concurrent checkpoint allocation, dependency withdrawal rollback, import interrupted before/after commit, media finalization failure, failed unlink, migration interruption at every durable step, checksum mismatch, disk-full backup and restoration of a matching schema/media set. No tests have been run for this design.
