# Implemented development scope

[Srpski](../sr/IMPLEMENTATION_STATUS.md)

Updated 2026-10-08. Unreleased development code; no production ZIP or support claim. This record supersedes the bootstrap-only status.

## The six requested steps

1. Private Players/Clubs create, search, edit, archive/restore, revision checks, validation and transaction/audit tests.
2. UUID memberships, club aliases and duplicate review; names never merge identities. Archive removes affected relationships and withdraws approved profiles.
3. Operator-selected database license in wp-admin: ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved or Custom. Custom accepts HTTPS URL or protected PDF/UTF-8 text upload (1 MiB). No default license. Standard terms URLs can be filled after explicit selection. Purpose, policy version, media terms and optional minor policy are separate inputs.
4. Protected JPEG/PNG processing and explicit publication approval with selected fields, private evidence and age review. Private edits never change approved copies. Policy changes withdraw previous approvals. Public media delivery closes on withdrawal; previously downloaded copies cannot be recalled.
5. Searchable public profiles via `[librett_registry]`, GET REST endpoints and bounded unsigned JSON snapshot export/import. Imports require private preview, explicit mappings and confirmation; fresh identities are unpublished. Receipts and expected revisions prevent duplicate/stale application. Source photo URLs are descriptors, never automatically fetched by WordPress.
6. LibreTT Desktop source/file import, preview/completion/mapping, local override retention, refresh and withdrawal provenance. Data flows registry → Desktop only. Historical registration snapshots remain unchanged. Desktop schema 22 retains mappings separately from local IDs and preserves deleted local profiles as detached links.

## Architecture and migrations

Independent application/domain ports; WordPress adapters own custom tables. Composition coordinates Players, Clubs, Publication and Media in transactions. Replication, Proposals and Recovery remain design modules. Migrations 001–006 create 22 empty custom tables; 001/002 definitions are retained. Feature migrations use advisory locks, checksums, resumable DDL and structural validation. Configured sites require operator backup acknowledgement. Deactivation/uninstall preserve data; destructive purge/private restore are absent.

Publication uses a permanent revision/tombstone ledger and approved copies, not a live dump of private drafts. Names and UUIDs are mandatory; optional public fields are explicitly approved. Unknown age blocks player publication; minors require a documented policy. Credentials, private evidence, draft audit and private storage paths are excluded from exports.

## Actual verification

Development environment: Fedora 44, rootless Podman; PHP 8.5.11, WordPress 7.1.3 single-site, MariaDB 10.11.19/InnoDB/GD. Composer dependencies are locked. Current executable commands and final results are in [verification](VERIFICATION_2026_10_08.md).

Suites cover private drafts, identities, additive schemas, memberships/aliases, approval/privacy, withdrawal, protected photos, custom licenses, JSON fixtures/resource bounds and transactional staged import. Desktop tests cover existing tournaments, migrations/backups plus registry import, local edits, idempotence, stale previews, rollback, withdrawal and historical snapshots. A synthetic WordPress-exported snapshot is also imported by SQLite Desktop and survives restart/backup restoration.

## Limits

No signatures, authenticated replica sync, proposals, authority recovery, automatic source discovery or first-contact freshness guarantee. Unsigned UUID/revision metadata does not establish trust. MySQL 8.4, multisite, production capacity and macOS/Windows integration are not verified here. Interactive GUI/browser and live public HTTPS deployment require operator acceptance. Rights/publication decisions remain the operator's responsibility.

Protected files require configured private storage outside all web-served roots. Database and private files need a coordinated backup. Failed writes can leave private orphan files; garbage collection is not implemented. PHP/server upload and database packet limits may be lower than the 32 MiB protocol limit. No federation dataset is bundled.
