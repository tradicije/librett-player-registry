# Publication policy — Phase 0 proposal

[Srpski](../sr/PUBLICATION_POLICY.md)

Status: adopted engineering design baseline, not an approved legal policy or implemented control. Exact JSON fields are specified in the bundled snapshot schema. No real dataset is authorized by this document.

Phase 0 update: adopted as the engineering baseline through [ADR 0004](../adr/0004-phase-0-engineering-baseline.md). The [readiness record](PHASE_0_READINESS.md) gives current status; [schema/validation examples](CONTRACT_VALIDATION.md) and [storage rules](STORAGE_AND_MIGRATIONS.md) refine this document. No legal authorization, runtime verification or authenticated format approval is implied.

## First catalogue

Propose a public catalogue for the first single-site milestone. Anonymous visitors and Desktop can read the same approved projection; private catalogue access is deferred. Administrative records remain protected. Public access does not itself grant redistribution rights: the operator must define dataset and media terms separately before enabling bulk export. Verified replication remains a later milestone.

Purpose: identify players and clubs for public profiles and optional tournament registration imports. Do not collect contacts, exact birth dates or identity documents for this milestone. No player accounts, tournament results or rankings are introduced.

## Dataset license selection — planned

The administrator will choose the database license in the plugin settings in `wp-admin`: **ODbL 1.0**, **CC0 1.0**, **CC BY 4.0**, **CC BY-SA 4.0**, **All rights reserved**, or **Custom**. For Custom, the administrator can provide a license URL or upload a license document. No dataset license is selected automatically. This settings workflow is planned and is not implemented yet.

Dataset licensing is separate from the plugin's AGPL-3.0-or-later license, personal-data publication authorization and photo permissions.

## Field allowlist

Only explicitly allowed fields enter public pages, search, API responses, snapshots or future feeds. Building a projection from all stored fields and removing known secrets is insufficient.

| Record / field | Proposed publication rule |
| --- | --- |
| Registry UUID, entity UUID, public revision, profile slug | Public metadata for published records; never WordPress row IDs |
| Player display name | Required for publication; nonblank, reviewed plain text |
| Given and family names | Optional, publish only when separately approved; do not infer by splitting the display name |
| Birth year | Optional and separately approved; never fabricate an absent year |
| Country and region | Optional, reviewed broad labels; no home address or precise location |
| Club identity/name and current membership | Optional; publish only the reviewed relationship to a published club |
| Biography | Optional reviewed plain text; no raw HTML or arbitrary structured achievements |
| Photo and public attribution | Optional; requires both profile publication approval and documented media permissions |
| Club name, abbreviation/aliases, country/region | Name required; other fields optional and reviewed |
| Credentials, moderation notes, audit actors, source evidence and publication authorization | Private; excluded from every public surface |

Detailed membership history, achievements and additional club fields are deferred. Unpublished club links and media cannot be exposed through a published player. Search indexes, counts, filters and direct lookup must use the same projection and cannot reveal drafts.

## Publication authorization

A new or imported record starts unpublished. An import's public flags do not authorize local publication. A future verified replica follows the accepted primary projection under the replication policy; it does not convert an arbitrary file into authority.

An authorized primary administrator prepares fields, records the publication purpose and authorization, then previews and explicitly publishes the selected projection. The application checks permission, expected revision, references and publication policy on every mutation. Permission to edit a profile does not automatically imply permission to approve its publication; exact WordPress capability names are decided later.

Store private authorization metadata: policy version, authorized scope, decision, approver, decision time and restricted evidence reference where needed. These are administrative facts, not public profile fields. The operator determines the lawful basis and evidence requirements; the software must not require consent as the only possible basis or claim that an administrator checkbox establishes compliance.

Profiles of minors remain unpublished unless a separate documented minor-publication policy and appropriate authorization workflow have been configured. Birth year alone is insufficient to establish an exact age or legal status; unknown status requires review. Synthetic examples are used until organizational policy is resolved.

Changes to approved public values or newly selected fields require explicit publication review. Previously published content remains the accepted projection while a private edit is pending, unless the administrator withdraws it immediately. Public export reads the accepted projection, never a working draft.

## Lifecycle

| Action | Public outcome | Private outcome |
| --- | --- | --- |
| Create/import draft | No public record, media, search entry or tombstone | Editable record and source information |
| Publish approved projection | Profile/search/API/export become consistent at one checkpoint | Approval and audit recorded |
| Save pending edits | Existing approved projection remains | Draft and review state retained |
| Archive | For the first milestone, withdraw from public surfaces | Record retained for administration |
| Withdraw publication | Remove profile, search entry and public media access; emit minimal tombstone if previously published | Authorization/history retained under operator retention policy |
| Purge | No personal fields in deletion events; maintain only necessary removal metadata | Delete personal data/media according to retention policy |
| Republish | Explicit new approval and newer public revision; never reuse a purged identity for another person | Record decision; cannot be triggered by a stale import |

Publication states are separate from administrative retention. Private storage enums await concrete DDL; public counters/revisions are specified in the snapshot contract. Public projections must not leak the number of private edits. Shared media remains accessible only if another approved public projection legitimately references it. Protected originals and derivatives need an adapter design that avoids exposing an unpublished Media Library URL; a hidden profile alone does not protect an attachment.

Withdrawal cannot erase copies already downloaded by Desktop or third parties. Desktop retains local profiles and tournament snapshots while recording upstream withdrawal. Neither stale imports nor policy edits automatically republish withdrawn records.

## Policy changes and release gate

Version the operator policy. A narrower scope removes affected public data atomically and records a checkpoint. A broader scope never automatically publishes previously private fields. Public policy metadata describes purpose, allowed fields, dataset/media terms and distribution scope; private authorization evidence is excluded.

Before real publication, resolve operator responsibility, lawful basis, minor handling, evidence access/retention, withdrawal requests, dataset redistribution and photo permissions. Before implementation, define the normative schema, bounded input limits, permissions, publication transaction and media storage behavior.

Planned review scenarios: draft absent from every public surface; private edit does not leak; forbidden nested fields rejected; unpublished references excluded; unauthorized approval rejected; import cannot self-publish; minor/unknown status held for review; withdrawal removes public derivatives; shared media retained only when legitimately referenced; stale data cannot resurrect a withdrawal; widened policy needs new approval. These are acceptance requirements, not executed tests.
