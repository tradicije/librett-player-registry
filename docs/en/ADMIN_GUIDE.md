# Administrator workflows — planned

[Srpski](../sr/ADMIN_GUIDE.md)

These describe future screens, not available installation instructions.

## Initial setup

Activation creates an empty setup state. Choose “Create a new registry” or “Host a replica”. Creating a registry assigns a fresh identity; connecting a replica preserves the source identity. Start with one registry per installation.

For a replica, paste a source connection URL or import a trusted connection file. Show name, registry ID, host, trust fingerprint and publication scope before confirmation. A registry ID alone is not a connection address. Verify the source before downloading real profiles.

## New primary

Provide a name and publication policy, define authorized administrators, and create/download the offline recovery material through the approved ceremony. No default federation/player content appears. Explain the difference between the operational key, the recovery authority and an admin password.

Add clubs before linking players where practical. Forms allow UUID-backed club selection, profile fields and separate publication choices. Moderators review duplicate candidates; do not merge solely by name. Primary CRUD is still subject to capabilities and revision checks.

## Public profiles

Provide player/club lists, search, filters and stable-identity profiles at changeable readable URLs. Public fields/photos are curated separately from private information. Show source/freshness on replicas. A federation may add editorial content without modifying mirrored player records.

## Replica

Sync shows last successful checkpoint, accepted primary, pending status and actionable error. Failed downloads keep the last accepted copy visible with a stale label. Admin controls presentation but cannot directly publish shared changes. “Propose addition/change/removal” records a separate request for primary moderation. Proposal access may require pairing even when public reading is open.

## Import/export and backup

Public JSON exports are portable publication data. Private organizational backup includes additional administrative data and must be protected separately. Neither contains the offline recovery secret. Media portability requires a verified package/download workflow; a WordPress attachment ID alone is insufficient.

Preview an import's origin, identity and counts/conflicts before confirmation. Copying an existing registry and continuing independently requires a new identity. Deactivation retains data. Never silently delete records during a plugin update/uninstall.

## Recovery

Use a valid backup/replica checkpoint and signed recovery authorization to assign a new primary/key. The former host need not respond. Record potential data loss and distribute the authority claim to peers/operators. The recovery procedure retains normal admin authorization; it does not make edits anonymous.

See [trust and recovery](TRUST_AND_RECOVERY.md). If the key is lost or claims conflict, show an explicit blocked recovery/conflict path rather than silently accepting a new primary.

## Languages and usability

Serbian and English UI, keyboard-accessible forms, readable field spacing, simple confirmation/preview screens, mobile public pages and understandable errors. Support data owned by any country; names and profile content are not automatically translated.
