# Module interfaces and use-case boundaries

[Srpski](../sr/MODULE_CONTRACTS.md)

**Development update (2026-10-08):** [Implemented scope](IMPLEMENTATION_STATUS.md) records migrations 001–006, private catalogue, publication/media, unsigned REST/JSON and Desktop import. Later trust/replica/recovery contracts and legal-policy proposals below remain proposals. Earlier Phase 0/bootstrap statements are historical; they do not describe current catalogue coverage.

Implemented ports include RegistryContextReader, draft readers/repositories, PlayerIdentityLookup, TouchPlayerDraftRevision, MembershipRepository, ClubAliases, PublicationStore, SnapshotValidator, ImportStore, ImportTarget, PhotoCatalogue, PhotoProcessor and ProtectedFiles. ArchiveRelations and DraftImportTarget coordinate module APIs within one shared transaction. Later interfaces below remain proposed contracts.

The following sections retain the Phase 0 design baseline; concrete source and verified scope take precedence for the development catalogue.

Later trust/storage contracts remain planned; multisite and nontransactional engines are excluded.

## Acyclic dependency graph

An arrow means imports of another module's public interface. RegistryIdentity and shared values have no module dependencies. Players → RegistryIdentity; Media → RegistryIdentity; Clubs → RegistryIdentity, Players (identity lookup only); Publication → RegistryIdentity, Players, Clubs, Media; Recovery → RegistryIdentity; Replication → Publication, Recovery, RegistryIdentity; Proposals → Publication, Players, Clubs, Media, RegistryIdentity. No reverse imports. Composition-level coordinators may call multiple public interfaces without becoming an all-purpose service. Each coordinator handles one workflow.

| Module | Public boundary | Private ownership |
| --- | --- | --- |
| RegistryIdentity | `RegistryContextReader`, `CreateRegistry`, `AssertPrimary`, later `AcceptAuthority` | Registry UUID, setup state, role, authority metadata |
| Players | `PlayerDraftReader`, `PlayerIdentityLookup`, `SavePlayerDraft`, `TouchPlayerDraftRevision`, `ArchivePlayer`, `PurgePlayer` | Private profiles, edit revisions, approval evidence |
| Clubs | `ClubDraftReader`, `MembershipDraftReader`, `SaveClubDraft`, `SetMemberships`, archive/purge | Club drafts, private membership graph |
| Media | `MediaDescriptorReader`, `PrepareMedia`, `FinalizeMedia`, `RestrictMedia`, `QueueMediaCleanup` | Validated private bytes, descriptors, rights |
| Publication | `PublishProfile`, `WithdrawProfile`, `ChangePolicy`, `PublicProjectionReader`, `SnapshotExporter` | Approved copies, public graph/revisions/events/checkpoint |
| Recovery | Later `VerifyAuthorityClaim`, `VerifyRootRotation` | Claim verification and trusted transition rules |
| Replication | Later `SyncSnapshot`, `ApplyChangePage` | Validated replica staging/cursor and accepted removal state |
| Proposals | Later `SubmitProposal`, `ReviewProposal` | Pending requests, base revisions, outcomes |

Publication coordinates approved dependencies through readers, never their tables. It owns public removal cascades; draft archive/purge coordinators invoke Clubs/Media/Publication public commands in one unit of work. Clubs queries player existence through `PlayerIdentityLookup`; Players never imports Clubs. A membership workflow coordinator calls `SetMemberships` and `TouchPlayerDraftRevision` in the same unit of work, including affected players on club archive/purge; Clubs never writes Players tables. Publication checks reader-provided versions of every affected draft/dependency before committing. A profile view composes public player and membership DTOs in Publication, avoiding domain cycles.

## Ports and security

Repositories and `UnitOfWork` are injected. `ActorContext` contains authenticated actor ID and capability grants from the WordPress adapter; use cases check operation permissions and primary role. `Clock`, `UuidGenerator`, `MediaStore`, `BoundedJsonReader`, `SnapshotValidator`, and later `PublicationSigner`, `SignatureVerifier`, `BoundedHttpClient`, `CredentialVerifier` are injected infrastructure ports. No module invokes WordPress, SQL, HTTP or filesystem directly. Domain inputs receive clock-derived values explicitly.

Candidate capabilities: `librett_registry_manage_settings`, `librett_registry_edit_profiles`, `librett_registry_publish_profiles`, `librett_registry_import`, `librett_registry_export`, `librett_registry_view_audit`, `librett_registry_purge`. Administrators receive the configured grants on setup; no default grant to anonymous visitors, players or all logged-in users. Do not infer a capability from hiding a button. WordPress UI mutations require capability plus authenticated session and action nonce; REST session writes also require REST nonce, while separately authenticated API clients require scoped credentials. Nonces never replace authorization. No remote mutation API is required for Phase 1. Private export/audit need capability even though public catalogue reads are anonymous.

Use cases return typed outcomes with stable codes (`permission_denied`, `revision_conflict`, `invalid_data`, `resource_limit`, `unsupported_schema`, `untrusted_authority`, `stale_checkpoint`, `identity_conflict`, `retryable_storage_failure`). WordPress translates messages and maps transport status; errors/logs contain no private payload, secret or full player record. Read/public projection ports never return administrative DTOs.

## First implementation slices

1. Composition/runtime preflight, empty migrations and setup identity; no sample data.
2. Players/Clubs private drafts and capabilities; explicit duplicate mapping.
3. Protected Media staging/finalization and private rights metadata.
4. Publication approval, public profiles/search and atomic withdrawal.
5. Bounded snapshot export/import, receipts and private staging.
6. Upgrade/uninstall policy and operator documentation.

Use server-rendered WordPress admin/public templates initially; no SPA, frontend framework or build tool is selected. Add small scripts only when needed, using strict TypeScript if a scripted frontend is introduced. Phases 2–5 interfaces remain design boundaries, not empty runtime modules. Dependency checks and isolated domain/use-case tests belong to authorized implementation verification.
