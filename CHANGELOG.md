# Changelog

Notable changes are recorded in English. No application release exists yet.

## Unreleased

### Added

- Began Phase 1 with a modular WordPress development bootstrap, Composer PSR-4 and independent RegistryIdentity domain/application ports using ramsey/uuid v4.
- Added bounded runtime/database preflight, an InnoDB initial identity/audit schema with advisory migration lock/checksum/resume checks, and capability/nonce-protected primary setup. Activation includes no registry UUID or player data; repeated setup preserves the original identity.
- Added English/Serbian setup UI and explicit retention on deactivation/uninstall; no purge, player CRUD, publication API or replica feature is implemented.
- Added locked development tooling, 16 unit tests and 14 real WordPress/MariaDB integration tests covering authorization, validation, rollback, migration failure/resume/lock and lifecycle retention. PHPStan max-level and supported PER 3.0 formatter checks passed; full PER 3.1 automation and production support are not claimed.

### Documentation

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
