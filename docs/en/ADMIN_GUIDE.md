# Administrator workflows — development and planned

[Srpski](../sr/ADMIN_GUIDE.md)

Phase 1 update: the [implemented bootstrap](IMPLEMENTATION_STATUS.md) records installed versions and actual tests. The remaining contracts below are planned; Phase 0 review statements refer to that historical documentation task.

The current draft slice is described separately below; subsequent screens remain planned.

First-milestone media is delivered from protected storage only after approval. Public snapshot import creates unpublished drafts with explicit identity mappings; it does not restore private approvals or confer source authority. See [storage/restore rules](STORAGE_AND_MIGRATIONS.md) and [readiness](PHASE_0_READINESS.md).

## Current private-draft administration

The development source adds private draft screens; runtime verification is pending. After fresh activation, create the primary registry in LibreTT Registry. For an existing configured bootstrap, open LibreTT Registry → Private-draft schema and confirm a verified backup before installing/resuming migration 002. This acknowledgement is the operator's responsibility; automated backup/restore is absent.

Administrators receive `librett_registry_edit_profiles`; authenticated users explicitly granted that capability can use Players and Clubs without gaining settings access. Create a draft, search by name, select a UUID-backed record, edit and save. To archive or restore, select Archived or Active and save. A stale revision returns a conflict; reload and review before resubmitting. Names may repeat, and saving does not merge records or publish them. Club memberships, aliases, media and purge remain pending.

## Planned later screens

## Initial setup

Activation creates an empty setup state. Choose “Create a new registry” or “Host a replica”. Creating a registry assigns a fresh identity; connecting a replica preserves the source identity. Start with one registry per installation.

For a replica, paste a source connection URL or import a trusted connection file. Show name, registry ID, host, trust fingerprint and publication scope before confirmation. A registry ID alone is not a connection address. Verify the source before downloading real profiles.

## New primary

Provide a name and publication policy, define authorized administrators, and create/download the offline recovery material through the approved ceremony. No default federation/player content appears. Explain the difference between the operational key, the recovery authority and an admin password.

Add clubs before linking players where practical. Forms allow UUID-backed club selection, profile fields and separate publication choices. Moderators review duplicate candidates; do not merge solely by name. Primary CRUD is still subject to capabilities and revision checks.

Follow the proposed [publication workflow](PUBLICATION_POLICY.md): prepare an unpublished draft, record private authorization, preview selected public fields and explicitly approve publication. Saving edits does not automatically publish them. Archive withdraws public access in the first milestone. Recovery material setup applies only when the later reviewed trust feature exists, not as a prerequisite for the initial single-site milestone.

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
