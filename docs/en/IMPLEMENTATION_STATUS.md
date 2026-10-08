# Phase 1 bootstrap — implemented scope

[Srpski](../sr/IMPLEMENTATION_STATUS.md)

Status: unreleased Phase 1 development build with the original verified bootstrap and an unverified private-draft extension. Phase 1 as a whole is not complete. No production deployment or plugin ZIP is published.

## Original bootstrap implementation

- Thin WordPress entry point and Composer PSR-4 composition root; RegistryIdentity domain/application stays independent of WordPress.
- Development preflight for 64-bit PHP 8.5, WordPress 7.1.3–7.1.x single-site, MariaDB 10.11 or MySQL 8.4, required text/database/media extensions and InnoDB. Future branches and multisite are rejected; these gates are engineering scope, not a universal runtime support promise.
- Original bootstrap activation created exactly three custom tables: migrations, identity setup singleton and private identity audit. It creates no registry UUID, player, club or publication policy and grants only the implemented settings capability to administrators.
- Initial migration uses a connection-scoped advisory lock, records checksum/started/completed state, verifies column/index/engine/collation structure, and resumes interrupted initial creation. It rejects changed/future schema and nontransactional tables. No upgrade/down-migration framework or private restore is implemented yet.
- A capability/nonce-protected POST creates one named primary registry using ramsey/uuid v4. Identity and audit commit together; repeated setup conflicts and preserves the original registry.
- English source UI and bundled Serbian Latin translation; escaped registry name/UUID rendering. Replica setup and public profiles are absent.
- Deactivation and uninstall preserve custom tables, identity and capabilities. There is no destructive purge option.

## Private player and club drafts — current development slice

Implemented in source on 2026-10-08; unreleased and not runtime verified. Separate Players and Clubs modules provide private creation, name search in pages of 50, editing, archive and explicit restoration by selecting Active. Players have display/given/family names, optional birth year, country, region and plain-text biography. Clubs have name, abbreviation, country and region. Duplicate names are allowed without merging. Club aliases, memberships, duplicate mapping, media, publication and purge are still pending.

Application readers and commands check `librett_registry_edit_profiles` and configured primary context. Every form mutation also checks POST, WordPress capability and an action nonce. UUIDs come from an injected ramsey/uuid v4 generator. Expected private edit revisions prevent stale overwrites; draft and module-owned audit commit in one transaction. Every accepted save advances the private revision, including an unchanged form. Archive retains data and UUID; it has no public effect because publication is absent.

Migration `002_private_drafts` adds four InnoDB tables (players, clubs and their separate private audits) with a checksum, advisory lock, resumable creation and structural verification. Fresh unconfigured activation installs both migrations, creating seven empty/setup tables without a registry UUID or sample profiles. Existing configured registries keep identity and data and use the capability/nonce-protected **Private-draft schema** page, where the operator confirms a verified database/media/configuration backup and retained matching code before installing or resuming the additive migration. The acknowledgement is not a verified backup or restore implementation. Incomplete/mismatched draft schema blocks draft pages and form mutations. Initial migration checksum stays unchanged. A general upgrade/down-migration or backup restore framework is still absent.

Current-session verification is limited to source/document review and patch whitespace inspection. PHP, Composer and gettext msgfmt are unavailable in this Linux workspace; no PHP syntax, PHPUnit, PHPStan, formatter, WordPress/database or browser check was run for this slice. The Serbian PO was updated and its MO artifact generated with Python standard-library tooling; gettext validation and runtime translation review remain pending. The earlier results below cover only the original bootstrap and do not verify this extension. Existing bootstrap tests remain unchanged; new draft/upgrade failure cases require separately authorized verification.

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

Complete Players/Clubs memberships, aliases, explicit duplicate mapping and purge workflows; publication policy/approval evidence; protected media; public projection/search/routes; bounded JSON parser/schema/import/export; public checkpoints/dependency withdrawal; upgrades, explicit purge and full release checks. Opis/parser dependencies are deferred until the import slice so the bootstrap adds only ramsey/uuid and its runtime dependencies. No empty future modules are shipped.
