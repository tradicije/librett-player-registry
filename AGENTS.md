# Instructions for contributors and coding agents

## Project status

LibreTT Player Registry has an unreleased single-site development catalogue: identity setup, migrations 001–006, private Players/Clubs, memberships/aliases, operator license settings, protected media, explicit publication, public API and bounded staged JSON import/export. One-way import is implemented in the sibling LibreTT Desktop development checkout. Replicas, proposals, signing/recovery, destructive purge and production release are absent. See docs/en/IMPLEMENTATION_STATUS.md and its verification record for actual scope. Read README.md, docs/en/PLAN.md and relevant architecture/protocol documentation before editing. User instructions override this file.

## Product boundaries

- One distributable WordPress plugin, with primary and replica roles. Creating an independent registry means creating a new primary with its own identity.
- Installation includes no player database. Registry records are created or imported after activation/setup.
- The product has public player profiles as well as administration and a desktop-readable API.
- Domain and application rules do not call WordPress, SQL, HTTP, filesystem, clocks or UI functions directly; use injected ports where required.
- WordPress adapters provide custom-table storage, admin UI, user/capability checks, HTTP routing and media integration. Custom tables alone do not make a product platform independent.
- Registry IDs and entity UUIDs must not depend on WordPress row IDs, attachment IDs, site URLs, slugs or human names.
- Desktop reads/imports registry data. Local desktop profile edits never update the online registry. A separate authenticated replica-proposal channel is not a desktop upload channel.
- Historical tournament snapshots must never be changed by registry refreshes.

## Data and trust invariants

- One accepted primary authority per registry generation. Replicas accept only validated, authenticated publications; hiding edit controls is not authorization.
- Recovery is a deliberate signed authority transfer, not a shared password bypass. An offline recovery authority is distinct from the ordinary server publication key.
- Never put the recovery private key in a public manifest, player export, replicated database, logs, repository or ordinary server backup. A new host receives a signed authorization, not the recovery secret.
- Signatures authenticate approved bytes; they do not establish legal ownership, freshness for a first-time client, or automatic discovery of a replacement site.
- Detect conflicting authority claims; never choose one solely by timestamp, hostname or an unchecked generation number.
- Imported identifiers do not justify automatically merging people with matching names. Handle mappings and local field overrides explicitly.
- Public projections exclude credentials, private moderation/audit data and non-public player fields.
- Personal-data publication needs a defined purpose, publication policy and authorization workflow. Do not assume every profile field should be public.

## Modular architecture and modern standards

Do not build a monolithic plugin. Keep RegistryIdentity, Players, Clubs, Publication, Replication, Proposals, Recovery and Media as modules with explicit interfaces and acyclic dependencies. One ZIP/composition root is acceptable; one giant service/controller or direct cross-module table access is not.

The documented development target is PHP 8.5, Composer PSR-4, strict types, explicit types/value objects and PHP-FIG PER Coding Style 3.1 (reviewed 2026-10-07). Recheck the latest stable/supported tooling before implementation and approve a WordPress/deployment compatibility matrix. Do not advertise an unverified runtime minimum. Use maintained libraries and strict TypeScript if a scripted frontend is introduced; no framework has been selected.

## Implementation discipline

- Implementation has been requested for Phase 1. Keep changes in the documented slice and do not create release versions or production installation/support claims without actual verification.
- Keep dependencies minimal. Select supported WordPress/PHP versions and tooling before adding requirements or check commands.
- Use WordPress capabilities plus request authentication/nonces as appropriate. Validate permissions on every server-side mutation.
- Validate imports before applying them; enforce bounded size/count/depth, transactional updates and idempotence.
- Bound remote requests; defend against SSRF, redirect abuse, unexpected content and decompression bombs. Remote data is untrusted even when signed.
- Use maintained cryptographic libraries. Do not implement signature algorithms by hand. Canonicalization, trust bootstrap, key rotation and recovery require fixtures and security review before shipping.
- Keep uninstallation and destructive operations explicit. Deactivation must preserve registry data.
- Read before editing; preserve unrelated user changes. Do not commit, push, publish, install dependencies or contact external accounts unless requested.
- Do not add or run tests unless the user asks for implementation testing or verification. When authorized, cover the relevant failure and migration cases, not just happy paths.

## Documentation and delivery

- Root AGENTS.md is the canonical agent instruction file (do not duplicate it as agents.md).
- README.md, CONTRIBUTING.md, SECURITY.md and docs/en/ have Serbian counterparts. Keep both languages consistent. English is the canonical protocol vocabulary; labels in the UI are translated.
- Update CHANGELOG.md and affected documentation after every change. Changelog entries are English under Unreleased.
- Record important design changes in docs/adr/. Keep proposed contracts separate from approved/shipped contracts.
- At completion, summarize changes and actual verification, disclose limits, and provide a suggested commit message. Never claim a runtime test passed when only documentation was reviewed.

## License

Copyright (C) 2026 Aleksa Dimitrijević. Project code and documentation are AGPL-3.0-or-later unless a file explicitly specifies a compatible exception. Preserve attribution and third-party notices. Player records, contributed photographs and federation datasets are not automatically licensed by the plugin's software license.
