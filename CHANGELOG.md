# Changelog

Notable changes are recorded in English. No application release exists yet.

## Unreleased

### Changed — PHP 8.3 hosting and installable development ZIP

- Accept 64-bit PHP 8.3.3–8.5.x for the user's WordPress 7.1.3 hosting; add the user's MariaDB 11.8 branch while retaining schema definitions/checksums and WordPress boundaries.
- Resolve dependencies against PHP 8.3.3 and use maintained PHPUnit 12 for cross-version tests; runtime dependency versions remain unchanged.
- Separate unsupported-PHP and missing-vendor activation diagnostics; include the actual PHP version and source-ZIP explanation.
- Add parameterized development images and runtime-only ZIP packaging with platform/autoload validation and checksum. See docs/en/PHP83_COMPATIBILITY.md for actual test/package results.


### Added — catalogue and Desktop integration (2026-10-08)

- UUID club memberships/aliases and explicit duplicate mapping; optimistic revisions and atomic archive hooks.
- Operator-selected database licenses, Custom HTTPS/upload terms, purpose/minor policy and explicit publication review.
- Protected photo derivatives and approval-gated delivery, public shortcode/search and GET API.
- Bounded unsigned snapshot parsing/schema/graph validation, export and staged transactional private import with receipts.
- One-way Desktop import/refresh with local overrides, source provenance and historical snapshot preservation in the sibling project.
- Authorized unit/integration, migration, rollback/privacy and cross-project synthetic roundtrip checks; bilingual scope, operator guidance and third-party notices. See docs/en/VERIFICATION_2026_10_08.md for actual results and limits.


### Added

- Added rootless Podman development tooling for PHP 8.5, Composer, required PHP extensions and gettext, plus an isolated MariaDB 10.11 socket and disposable WordPress 7.1.3 setup; local data stays outside version control. Installed PHP 8.5.11, Composer 2.10.3, gettext 0.21 and 69 locked packages; manifest/platform/audit and WordPress/database setup checks passed, without executing implementation test suites.

- Added separate Players/Clubs private draft modules, bounded fields/name search, stable UUIDs, capability/nonce-protected admin forms, optimistic edit revisions, transactional private audit and archive/restore. Memberships, aliases, duplicate mapping, purge and publication remain pending.
- Added additive private-draft migration 002 with lock/checksum/resume/structure checks, fresh-install setup and explicit backup acknowledgement before upgrading an existing configured registry; original identity and migration checksum are retained.
- Updated Serbian draft/migration UI catalogue and generated MO artifact. This slice has not been executed in PHP/WordPress; earlier bootstrap verification does not cover it.

- Began Phase 1 with a modular WordPress development bootstrap, Composer PSR-4 and independent RegistryIdentity domain/application ports using ramsey/uuid v4.
- Added bounded runtime/database preflight, an InnoDB initial identity/audit schema with advisory migration lock/checksum/resume checks, and capability/nonce-protected primary setup. Activation includes no registry UUID or player data; repeated setup preserves the original identity.
- Added English/Serbian setup UI and explicit retention on deactivation/uninstall in the original bootstrap; that first slice included no purge, player CRUD, publication API or replica feature.
- Added locked development tooling, 16 unit tests and 14 real WordPress/MariaDB integration tests covering authorization, validation, rollback, migration failure/resume/lock and lifecycle retention. PHPStan max-level and supported PER 3.0 formatter checks passed; full PER 3.1 automation and production support are not claimed.

### Documentation

- Documented English/Serbian container setup, tool invocation and database start/stop commands; this installation does not imply private-draft test coverage or production support.

- Clarified planned one-way registry-to-Desktop downloads, offline local storage and local-only edits, protecting both local data and the online registry through this import channel.
- Recorded the unverified private-draft implementation, additive migration/operator backup workflow and next slice in English/Serbian documentation and ADR 0006.

- Specified administrator-selected dataset licensing in wp-admin: ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved and Custom with a license URL or uploaded document; settings implementation remains planned.

- Added bilingual development commands and implementation/verification records, ADR 0005 and runtime third-party notices; updated project/agent status and affected plans/policies without claiming Phase 1 completion.

- Completed the Phase 0 documentation/engineering baseline with a local JSON Schema 2020-12 for unsigned public snapshots and 11 synthetic acceptance/rejection documents; specified mandatory parser, semantic and contextual checks without claiming fixture execution.
- Specified module interfaces and acyclic dependencies, private/public storage ownership, InnoDB transaction rules, protected media finalization/withdrawal, resumable migrations and explicit backup/uninstall behavior.
- Reviewed official runtime/tooling sources and selected PHP 8.5, WordPress 7.1.3 single-site, MariaDB 10.11/MySQL 8.4 targets and maintained tooling families; recorded implementation spikes, exact-version resolution and runtime verification gates.
- Recorded the separate authenticated-publication/authority-chain design review, accepted engineering ADR 0004 and bilingual Phase 0 readiness records; aligned prior ADR status, READMEs, plans, indexes, contribution/security policies and affected documentation. No plugin implementation, dependency install, runtime tests or production approval are included.

- Added bilingual public snapshot contract draft 1 and ADR 0003 specifying strict field types, decimal-string counters, bounded graph validation, public/private revision separation, dependency withdrawal and atomic staged import identity/idempotence rules. Signed replication and private restore remain separate pending contracts.
- Updated README links, documentation indexes, Phase 0 progress, protocol, data model and Desktop integration references; no schema approval, runtime implementation or executed tests are claimed.

- Started Phase 0 with a bilingual publication-policy proposal and ADR 0002 covering public catalogue scope, explicit field allowlists, private drafts/approval evidence, media access and withdrawal semantics. Legal authorization, schema and runtime decisions remain open.
- Linked the proposal from READMEs and documentation indexes; aligned plan progress, data model, protocol and administrator workflows without claiming implementation or completed verification.

- Established LibreTT Player Registry as one WordPress plugin with independent core layers and primary/replica roles; installation includes no player dataset.
- Added bilingual README, plan, architecture, data model, protocol proposal, desktop import, trust/recovery and administrator workflow documentation.
- Added contribution and security policies, canonical AGENTS.md, an architecture decision, repository hygiene files and the AGPL-3.0-or-later license.
- Documented one-way desktop imports with local overrides, stable cross-host identity, moderated proposals and recovery without an available original host. Implementation and security acceptance criteria remain pending.
- Documented a non-monolithic module design, modern PHP 8.5 development target, PSR-4 and PER Coding Style 3.1; runtime compatibility and tooling remain pending verification before implementation.
