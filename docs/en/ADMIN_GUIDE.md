# Administrator guide — development catalogue

[Srpski](../sr/ADMIN_GUIDE.md)

This is unreleased source for a disposable development site. Read [scope/limits](IMPLEMENTATION_STATUS.md) before using real data. No players/clubs are included.

## Installation and setup

1. Use the selected development matrix: 64-bit PHP 8.3.3–8.5.x, WordPress 7.1.3 single-site, MariaDB 10.11 or 11.8/InnoDB; install Composer runtime dependencies. MySQL 8.4 remains unverified.
2. Activate the plugin; fresh activation creates empty schema and administrator capabilities. Open **LibreTT Registry** and create your own named primary registry.
3. Existing configured installations: back up database and protected files, then use **Registry schema** to acknowledge your backup and install/resume additive migrations 001–006. The acknowledgement does not create a backup. Restart/retry resumes verified DDL; never edit recorded checksums to bypass errors.
4. For photo/Custom document uploads, provision a writable directory owned by the PHP user, mode 0700, outside the document root, WordPress directory and every web-server alias. Set `LIBRETT_PRIVATE_STORAGE` in wp-config.php to that absolute directory (or supply the environment variable). Stored files use 0600. No public uploads directory fallback exists; CRUD without uploads needs no private directory.

### Installing the prepared development ZIP

GitHub's **Code → Download ZIP** contains source only and excludes `vendor`; it cannot be activated directly. Use `build/librett-player-registry-development.zip`, generated with `tools/package-plugin`, which includes locked runtime dependencies. In Plugins → Add New → Upload Plugin, select that ZIP. If the source-only copy is already installed, use WordPress's replacement confirmation, then activate. Existing registry data is retained. No numbered production release is created.

This package is prepared for the user's PHP 8.3.3 / WordPress 7.1.3 single-site combination. Actual test evidence is in [PHP 8.3 compatibility](PHP83_COMPATIBILITY.md). Database/extensions/private-storage requirements still apply; host settings are checked at activation.

## Private catalogue

Create clubs and players in **Clubs**/**Players**. UUID identifies the record; repeated names are allowed. Choose Active/Archived to restore/archive and save with the displayed revision. A conflict requires reloading and reviewing current data. No automatic name-based merge exists.

In **Memberships, aliases and duplicate review**, inspect candidates and their UUID/details. Enter active club UUIDs for a player (maximum 100), or club aliases (maximum 20). Membership changes advance the player revision. Archiving a club removes affected memberships; archive withdraws its approved public copy. Reopening a draft never republishes it automatically.

## License and publication settings

Choose ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved or Custom explicitly. Standard license links can be supplied automatically after that choice; reserved rights need your terms URL. Custom uses either an HTTPS URL or UTF-8 text/PDF upload up to 1 MiB. Empty Custom inputs retain a previously saved document. Document delivery is a download; no recovery secret or private approval evidence belongs in it.

Supply policy version, purpose and media terms HTTPS URL; optional minor policy is required before minor approval. Licenses do not establish publication permission. Change policy version for every policy change: previous publications are withdrawn atomically and require review again.

## Photos and public approval

**Player photographs** accepts JPEG/PNG up to 5 MiB and 4096 pixels per side. Record public attribution and private rights evidence. GD decodes/re-encodes images, stripping original metadata/trailing bytes. Private previews require editor permission. Save/photo removal does not itself publish the draft.

In **Publication review**, inspect data, select optional public fields, review age and provide a private authorization reference. Publish clubs before approving linked player memberships. Name/UUID are public requirements. Unknown age blocks approval; minor approval needs the configured policy. Saving later edits leaves the last approved copy unchanged. Explicit withdraw/archive closes public access and photo delivery; downloaded copies remain outside server control.

## Public site and JSON

Add `[librett_registry]` to a WordPress page for profile/search rendering. GET routes under `/wp-json/librett-registry/v1/`: `manifest`, `snapshot`, `players`, `clubs`, `players/{uuid}`, `clubs/{uuid}`, `media/{uuid}`, `license`. Search uses `q`, `offset`, `limit` (maximum 50). Snapshot JSON is the complete approved public projection, unsigned format v1; save the `snapshot` response for file transfer. It excludes private fields and evidence.

**JSON import** validates a file up to 32 MiB, stages a private preview and asks for confirmation. Map existing records only through explicit `player|club source-UUID local-UUID` lines; otherwise new independent UUIDs or saved provenance are used. Review source fields/target revisions before confirming. Supplied fields and links update drafts; omitted optional fields remain local. Imports never approve publication or delete records on source withdrawal. Source photo descriptors are retained without downloads. Previews expire after one hour; cancel unused previews (maximum five per operator).

PHP/proxy upload settings and database `max_allowed_packet` must accommodate actual accepted JSON and mapping payloads; the protocol bound is not a server capacity promise. Backups must include private tables and the separate protected directory. Database-only recovery cannot recover private photo/license bytes. Automatic private restore, purge and orphan-file cleanup are not implemented.

## Desktop

Open Desktop **Players** and the registry import panel. Use the HTTPS `snapshot` URL or downloaded JSON. Review mappings, complete missing birth years/local field limits, explicitly fetch/crop photos if wanted, then confirm. Local edits and historical registrations remain local. Refresh uses source identity/checkpoint and retained override choices. No local notes, contacts, payments or tournament data are uploaded.

## Permissions

`librett_registry_manage_settings`: setup/schema/policy. `librett_registry_edit_profiles`: private catalogue/media. `librett_registry_publish_profiles`: publication, with edit permission for inspection. `librett_registry_import` plus edit permission: JSON import. Server-side mutations require POST, capability checks, action nonce and application authorization. Public endpoints serve approved projections only. Deactivation/uninstall retain records and capabilities.

Import previews also expire when source-to-local mappings change after preparation. Cancel the stale preview and stage the file again. Pending previews created before the mapping-baseline correction must also be cancelled and recreated; completed imports remain valid.
