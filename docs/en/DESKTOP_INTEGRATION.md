# LibreTT Desktop integration

[Srpski](../sr/DESKTOP_INTEGRATION.md)

Status: planned; the registry integration is not currently implemented in Desktop.

The proposed [snapshot contract](SNAPSHOT_CONTRACT.md) uses decimal-string counters, omitted optional fields and explicit source identity. Parse counters without precision loss; omitted fields never clear local values. The unsigned draft cannot authenticate authority or establish reliable remote freshness.

## User workflow

In Players, add “Update Player Registry” and JSON import/export workflows. Configure a source URL or trusted connection file; an initial default may point to librett.org but must be replaceable. The same registry may later live on stoni.rs or another organization’s site. A public registry does not require a player account; private sources need a separate read-only authorization design.

Fetch/validate → preview additions and changes → resolve mappings/required fields → confirm transaction → keep records available offline. Failure retains the existing local database and accepted cursor. A profile link opens the current remote public page, not a hard-coded hostname.

## Stored provenance

For a linked local player, retain local player UUID, registry UUID, remote player UUID, accepted authority/checkpoint, remote entity revision, last imported normalized field values and explicit local override state. Remote metadata is separate from the local player profile. Map imported clubs similarly; remote club IDs do not replace local IDs.

One local player can eventually have reviewed links to multiple registries; first release may limit this. A duplicate name is a review candidate, never an automatic merge. Linking a manually entered profile is explicit and cannot replace historical registrations.

## Refresh and local edits

Compare previous remote value B, current local value L and new remote value R per field:

| Condition | Action |
| --- | --- |
| No local override and L = B | Apply R if valid |
| Local override; upstream unchanged | Keep local value |
| Local override; upstream also changed | Keep local value by default; offer reviewed acceptance of upstream |
| User chooses “use registry value” | Apply current R and clear that field's override |

An explicit local override remains an override even if values happen to become equal. Treat a clear-to-null as a local edit. Missing remote optional/public fields have specified semantics; do not silently clear local fields. Validate complete Desktop profile invariants before saving.

Online birth year may be absent. Since Desktop requires birth year, require organizer completion or keep the profile as a pending import instead of fabricating a year. Download photos through bounded validation/cropping rules; remote URLs are not silently stored where Desktop expects validated image bytes.

## Removal and stale sources

A tombstone marks the upstream profile unavailable; preserve local player and tournament history. A later stale snapshot must not resurrect upstream publication or overwrite newer provenance. A new authority generation is reviewed using the trusted transition, not compared solely by numeric revision.

Normal refresh does not delete manual local players or strip local club names. Importing a full registry snapshot is not restoring the entire Desktop backup.

## One-way guarantee

No desktop profile edits, payments, attendance or tournament records are uploaded. The registry API client has read/import capabilities only. Proposal submission belongs to authorized registry sites, not an implicit desktop feature. Remote links/provenance cannot bypass local validation or scoring rules.

## Acceptance cases

First/repeated import, migrated source domain, same names/different identities, human-approved linking, local edit with upstream change, clearing a field, absent birth year, invalid media, upstream purge, schema mismatch, interrupted import, offline use and preservation of historical snapshots. Desktop changes belong in its repository when implementation is requested.
