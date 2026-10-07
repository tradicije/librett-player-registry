# Interfejsi modula i granice use case-ova

[English](../en/MODULE_CONTRACTS.md)

Status: osnova dizajna faze 0. Nazivi predstavljaju buduće PHP interfejse, ne postojeće klase. Mali zajednički UUID/counter/error/actor tipovi nemaju infrastrukturne zavisnosti.

## Graf bez ciklusa

Strelica znači import javnog interfejsa drugog modula. RegistryIdentity i shared vrednosti nemaju zavisnosti od modula. Players → RegistryIdentity; Media → RegistryIdentity; Clubs → RegistryIdentity, Players (samo pronalaženje identiteta); Publication → RegistryIdentity, Players, Clubs, Media; Recovery → RegistryIdentity; Replication → Publication, Recovery, RegistryIdentity; Proposals → Publication, Players, Clubs, Media, RegistryIdentity. Bez obrnutih import-a. Composition koordinatori mogu pozvati više javnih interfejsa; svaki obrađuje jedan tok, bez univerzalnog servisa.

| Modul | Javna granica | Privatno vlasništvo |
| --- | --- | --- |
| RegistryIdentity | `RegistryContextReader`, `CreateRegistry`, `AssertPrimary`, kasnije `AcceptAuthority` | UUID registra, setup, uloga i autoritet |
| Players | `PlayerDraftReader`, `PlayerIdentityLookup`, `SavePlayerDraft`, `TouchPlayerDraftRevision`, `ArchivePlayer`, `PurgePlayer` | Privatni profili, edit revizije, dokazi odobrenja |
| Clubs | `ClubDraftReader`, `MembershipDraftReader`, `SaveClubDraft`, `SetMemberships`, archive/purge | Nacrti klubova i privatni graf članstva |
| Media | `MediaDescriptorReader`, `PrepareMedia`, `FinalizeMedia`, `RestrictMedia`, `QueueMediaCleanup` | Provereni privatni bajtovi, opisi i prava |
| Publication | `PublishProfile`, `WithdrawProfile`, `ChangePolicy`, `PublicProjectionReader`, `SnapshotExporter` | Odobrene kopije, javni graf/revizije/događaji/checkpoint |
| Recovery | Kasnije `VerifyAuthorityClaim`, `VerifyRootRotation` | Provera tvrdnji i pravila prenosa poverenja |
| Replication | Kasnije `SyncSnapshot`, `ApplyChangePage` | Proveren staging/cursor i prihvaćeno uklanjanje |
| Proposals | Kasnije `SubmitProposal`, `ReviewProposal` | Zahtevi na čekanju, početne revizije i ishodi |

Publication koordinira odobrene zavisnosti kroz reader-e, ne tabele. Drži javna zavisna uklanjanja; draft archive/purge koordinatori pozivaju javne Clubs/Media/Publication komande u jednom unit of work-u. Clubs proverava igrača kroz `PlayerIdentityLookup`; Players ne import-uje Clubs. Koordinator članstva poziva `SetMemberships` i `TouchPlayerDraftRevision` u istom unit of work-u, uključujući igrače pri archive/purge kluba; Clubs ne upisuje Players tabele. Publication pre upisa proverava verzije svih obuhvaćenih nacrta/zavisnosti iz reader-a. Publication sastavlja javne player/membership DTO-e i izbegava cikluse.

## Portovi i bezbednost

Repositories i `UnitOfWork` su injektovani. `ActorContext` ima autentifikovan ID aktera i capability grants iz WordPress adaptera; use case proverava dozvolu radnje i primary ulogu. `Clock`, `UuidGenerator`, `MediaStore`, `BoundedJsonReader`, `SnapshotValidator`, kasnije `PublicationSigner`, `SignatureVerifier`, `BoundedHttpClient`, `CredentialVerifier` su infrastrukturni portovi. Modul ne poziva WordPress, SQL, HTTP ili filesystem direktno. Vrednosti izvedene iz sata izričito se prosleđuju domenu.

Kandidati capabilities: `librett_registry_manage_settings`, `librett_registry_edit_profiles`, `librett_registry_publish_profiles`, `librett_registry_import`, `librett_registry_export`, `librett_registry_view_audit`, `librett_registry_purge`. Administratori dobijaju podešene grants tokom setup-a; nema automatskih dozvola za publiku, igrače ili sve prijavljene korisnike. Skriveno dugme nije autorizacija. WordPress UI mutacije zahtevaju capability, autentifikovanu sesiju i action nonce; REST session upis takođe REST nonce, posebno autentifikovan klijent scoped kredencijal. Nonce ne zamenjuje dozvolu. Remote mutation API nije potreban fazi 1. Privatni izvoz/audit traže capability iako je javni katalog anoniman.

Use case daje tipiziran ishod sa stabilnim kodom (`permission_denied`, `revision_conflict`, `invalid_data`, `resource_limit`, `unsupported_schema`, `untrusted_authority`, `stale_checkpoint`, `identity_conflict`, `retryable_storage_failure`). WordPress prevodi poruke i mapira transport status; greške/log nemaju privatni payload, tajnu ili ceo profil. Javni reader ne vraća administrativni DTO.

## Prvi implementacioni koraci

1. Composition/runtime preflight, prazne migracije i setup identiteta, bez demo podataka.
2. Privatni Players/Clubs nacrti i dozvole; izričito mapiranje duplikata.
3. Zaštićen Media staging/finalizacija i privatna prava.
4. Odobrenje Publication, javni profili/pretraga i atomsko povlačenje.
5. Ograničen izvoz/uvoz snimka, potvrde i privatan staging.
6. Upgrade/uninstall pravila i uputstva operatoru.

Početno server-rendered WordPress admin/javni templates; bez SPA, framework-a ili build alata. Skripte dodati samo po potrebi, uz strict TypeScript kada se uvodi scripted frontend. Interfejsi faza 2–5 su granice dizajna, ne prazni runtime moduli. Provera zavisnosti i izolovani domain/use-case testovi pripadaju autorizovanoj implementacionoj verifikaciji.
