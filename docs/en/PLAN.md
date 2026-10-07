# Development plan

[Srpski](../sr/PLAN.md)

Status: proposed implementation milestones; no application code exists.

## Scope

One modular plugin supports an empty independent registry or a replica, public player/club profiles, administration, portable JSON and one-way LibreTT Desktop imports. Later milestones add verified replication, moderated proposals and offline-authorized recovery. The package contains no federation dataset.

## Current work

Phase 0 documentation preparation is complete; see the [readiness record](PHASE_0_READINESS.md) and [ADR 0004](../adr/0004-phase-0-engineering-baseline.md). Publication rules, unsigned structural JSON Schema/examples, storage/migrations, module interfaces and reviewed runtime/tool targets are recorded as the engineering baseline. Runtime/dependency/fixture execution remains implementation-time work, and real-data approval and authenticated-format security review remain deployment/feature gates. No application code, dependencies or tests have been added. Next is the Phase 1 bootstrap slice described in [module contracts](MODULE_CONTRACTS.md), when implementation is requested.

## Phase 0 — contracts and technical foundation

- Confirm publication/privacy fields, dataset/media rights and whether the first catalogue is public only.
- Adopt module boundaries, acyclic dependencies and explicit ports; no giant plugin file, all-purpose service or controller.
- Development target: PHP 8.5, the highest supported branch listed by PHP on 2026-10-07. Confirm WordPress/database compatibility and minimum deployment versions before claiming support. Recheck versions when implementation begins.
- Composer PSR-4, strict types, typed DTOs/value objects and current PHP-FIG PER Coding Style (3.1 at review date). Avoid older-runtime compatibility work unless deliberately agreed.
- Choose static-analysis, formatter, test and dependency-audit tools; record exact versions and commands when installed. No tooling has been installed now.
- Review schema/versioned JSON, UUID identity, transaction boundaries, conflict rules, bounded import and migration rollback.
- Review recovery trust/bootstrap and canonical signed-envelope design before representing any replica as authenticated.

Exit: reviewed contracts, runtime matrix and ADRs; nothing advertised as implemented.

## Phase 1 — single-site registry

- Activation creates schema, capabilities and setup state, not sample player records or an automatic registry identity.
- Setup creates a new registry and stable IDs. Separate public records from administrative data.
- Players/clubs CRUD, search, validation, duplicate review, archived/purged states and audit trail.
- WordPress admin and public routing/templates without CPT ownership of registry records.
- Media adapter with verified uploads, ownership/publication metadata and portable references.
- Publication projection and full portable snapshot import/export, validated before application.
- Deactivation preserves data; explicit uninstall/purge handles retention with confirmation.

Exit: empty install, synthetic profiles, JSON round trip, migration/restart, permissions, privacy projection and deletion behavior verified. No replicas yet.

## Phase 2 — Desktop imports

- Configure an HTTPS source or import a JSON file; source domain is not identity.
- Persist registry/player mapping, last imported projection and local field override decisions.
- Preview additions/updates/conflicts before confirmation; no uploads to registry.
- Preserve manual local players, club links, historical tournament snapshots and offline usability.
- Handle optional remote birth year/photos without bypassing desktop validation.

Exit: initial import, repeated import, changed source URL, local edits, stale snapshot and deleted remote profile scenarios pass.

## Phase 3 — verified replicas

- Trusted connection bundle/manifest, publication keys, signed snapshots/change feeds and migration limits.
- Replay/rollback protection, revisions scoped to authority generation, atomic cursors and full resync.
- Public profiles and desktop API served from verified local copies, with source and freshness labels.
- Duplicate-feed requests, interrupted pages, deletions, media loss, key rotations and authority conflicts.
- Shared-record edits rejected server-side; no false promise against a hosting owner editing their own files.

Exit: unauthenticated/malformed/stale publications cannot replace accepted state; repeat synchronization converges. Trust review required before public deployment.

## Phase 4 — proposals

- Pair replica identities and grant/revoke proposal credentials scoped to a registry.
- Add/change/remove proposals, base revisions, idempotency, rate limits and moderation outcomes.
- Only primary acceptance creates a shared data revision. Private evidence is not publicly replicated.

Exit: unauthorized proposals rejected, stale proposals reviewed, retry produces one proposal/change, and requester receives outcome.

## Phase 5 — authority transfer and recovery

- Offline recovery authority authorizes a new operational key and primary endpoint.
- Independent data restore plus signed handover claims; old server need not exist.
- Persist accepted authority generation; reject rollback and conflicting same-generation claims.
- Rotation, compromised operational keys, loss of recovery key and replica operator discovery/manual import paths.
- Recovery of stale data explicitly declares its checkpoint; do not reuse conflicting revision numbers.

Exit: simulated primary loss, returning old host, disconnected replicas, replay and concurrent recovery claims produce explicit correct outcomes.

## Phase 6 — release readiness

Bilingual admin/public UI, accessible responsive profiles, performance limits, export compatibility, signed update/release provenance appropriate to distribution, private disclosure channel and operator documentation. Publish plugin ZIP only after actual runtime checks. A docs-only folder is not an installer.

## Deferred

Concurrent editable primaries, automatic leader election, cross-registry identity merging, desktop-to-registry edits, tournament scoring, phone referee input, ranking calculation and user self-service profile accounts. Federation-specific data and editorial workflows need separate decisions.

## Sources and open decisions

[PHP support](https://www.php.net/supported-versions.php), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [PSR-4](https://www.php-fig.org/psr/psr-4/). Unsigned snapshot v1 structure/limits, first public catalogue and engineering targets are recorded in the Phase 0 baseline. Concrete REST routing, installed dependency/parser configuration, tested runtime support, portable private media packaging and normative signed/key-ceremony formats remain implementation or later feature work; see the readiness gates.
