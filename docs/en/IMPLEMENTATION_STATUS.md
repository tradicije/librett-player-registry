# Phase 1 bootstrap — implemented scope

[Srpski](../sr/IMPLEMENTATION_STATUS.md)

Status: unreleased development bootstrap, first slice of Phase 1. Phase 1 as a whole is not complete. No production deployment or plugin ZIP is published.

## Implemented

- Thin WordPress entry point and Composer PSR-4 composition root; RegistryIdentity domain/application stays independent of WordPress.
- Development preflight for 64-bit PHP 8.5, WordPress 7.1.3–7.1.x single-site, MariaDB 10.11 or MySQL 8.4, required text/database/media extensions and InnoDB. Future branches and multisite are rejected; these gates are engineering scope, not a universal runtime support promise.
- Activation creates exactly three custom tables: migrations, identity setup singleton and private identity audit. It creates no registry UUID, player, club or publication policy and grants only the implemented settings capability to administrators.
- Initial migration uses a connection-scoped advisory lock, records checksum/started/completed state, verifies column/index/engine/collation structure, and resumes interrupted initial creation. It rejects changed/future schema and nontransactional tables. No upgrade/down-migration framework or private restore is implemented yet.
- A capability/nonce-protected POST creates one named primary registry using ramsey/uuid v4. Identity and audit commit together; repeated setup conflicts and preserves the original registry.
- English source UI and bundled Serbian Latin translation; escaped registry name/UUID rendering. Replica setup and public profiles are absent.
- Deactivation and uninstall preserve custom tables, identity and capabilities. There is no destructive purge option.

## Actual verification

On 2026-10-07, PHP 8.5.5 CLI, WordPress 7.1.3, MariaDB 10.11.19 InnoDB, macOS/Homebrew, GD backend:

| Check | Result |
| --- | --- |
| PHPUnit 13.4.1 unit suite | 16 tests, 42 assertions passed |
| PHPUnit 13.4.1 bootstrapped WordPress integration suite | 14 tests, 43 assertions passed |
| PHPStan 2.3.0 | Level max, all `src/` classes, no errors; WordPress stubs 7.1.2 |
| PHP CS Fixer 3.95.27 | 23 PHP files checked, no remaining changes under supported `@PER-CS3x0` |
| Composer 2.10.3 | Strict manifest validation passed; dependency installation audit reported no advisories |
| gettext msgfmt | Serbian catalogue compiled/checked |

Core cases cover permission denial, name length/Unicode/controls/encoding, UUID creation and unsupported runtime targets. Integration cases cover empty/repeated schema, setup conflict, audit failure rollback, interrupted DDL, checksum/future-schema mismatch, MyISAM rejection, nested transaction rollback, migration lock contention, capability/nonce checks, successful POST/escaped rendering, activation/deactivation/uninstall retention. Fixtures are synthetic; email and external WordPress HTTP are blocked.

PHP CS Fixer does not supply a PER 3.1 preset at this locked release. The project design target remains PER 3.1; automatic enforcement currently covers its supported 3.0 rules. Complete 3.1 automated coverage is not claimed. No browser visual/accessibility review, Linux deployment, MySQL run, exhaustive crash/migration drill, production backup restore or signing/import/media test is claimed.

## Remaining Phase 1 work

Players/Clubs drafts and CRUD; publication policy/approval evidence; protected media; public projection/search/routes; bounded JSON parser/schema/import/export; public checkpoints/dependency withdrawal; upgrades, explicit purge and full release checks. Opis/parser dependencies are deferred until the import slice so the bootstrap adds only ramsey/uuid and its runtime dependencies. No empty future modules are shipped.
