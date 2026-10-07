# ADR 0005: Begin Phase 1 with isolated primary identity setup

Date: 2026-10-07
Status: Implemented development slice; unreleased and not production approved.

## Decision

Implement only the first bootstrap slice: Composer PSR-4, independent RegistryIdentity use case/value objects, injected UUID/repository/transaction ports, WordPress preflight, migration lock/checksum verification, setup UI and data retention. Activation creates migration/identity/audit tables and a null singleton, never a registry UUID or player data. An authorized nonce-checked administrator POST creates one random UUIDv4 primary and audit transactionally. Replica/publication/API and player CRUD are deferred.

Use ramsey/uuid 4.9.4 and locked runtime dependencies. The user authorized development dependencies and implementation testing in this session. A disposable WordPress 7.1.3 / MariaDB 10.11.19 site verifies the slice on PHP 8.5.5; email/external HTTP are blocked. MySQL/Linux deployment and later migrations remain unverified. Deactivation/uninstall retain data/capabilities; destructive purge is absent.

PHP CS Fixer 3.95.27 has a PER 3.0 ruleset, not 3.1. Use the supported explicit ruleset and disclose coverage; the project target remains PER 3.1. WordPress stubs 7.1.2 support max-level analysis of all source classes.

## Consequences

Development installation needs Composer runtime dependencies. Initial migration is resumable, verifies existing DDL and refuses mismatched/future schema; no general upgrade or backup/restore runner is claimed. Domain/application contain no WordPress calls. See bilingual [implemented scope](../en/IMPLEMENTATION_STATUS.md) / [implementiran obim](../sr/IMPLEMENTATION_STATUS.md) for actual tests, limitations and next Players/Clubs slice.
