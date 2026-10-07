# LibreTT Player Registry

A portable player and club registry with public profiles, WordPress administration, and one-way imports into LibreTT Desktop.

![License: AGPL-3.0-or-later](https://img.shields.io/badge/License-AGPL--3.0--or--later-3da639.svg)
![Status: Design](https://img.shields.io/badge/Status-Development-818cf8.svg)
![WordPress adapter: planned](https://img.shields.io/badge/WordPress_adapter-bootstrap-21759b.svg)

[Srpski](README-sr.md)

**Status: early implementation.** An unreleased WordPress development bootstrap provides empty initial migrations and primary registry identity setup. Player/club CRUD, public profiles/API, import/export and synchronization remain planned. See [implemented scope and actual verification](docs/en/IMPLEMENTATION_STATUS.md).

## What we are building

One plugin named **LibreTT Player Registry** (`librett-player-registry`) can create a new empty registry or host a replica of an existing registry. It includes administration, public searchable profiles and an API; WordPress is the first adapter, rather than part of the domain model.

A federation or club in any country can create its own dataset. No Serbian player database is bundled with the plugin. One registry can move between domains or be mirrored by multiple sites without changing player identities.

## Core decisions

- Custom tables for registry data; no dependency on player/club CPTs or postmeta.
- Independent domain/application layers and replaceable persistence/HTTP/media adapters.
- Stable registry identity and player/club UUIDs, with separate public profile slugs.
- A primary publishes accepted changes. Replicas serve validated copies and can submit proposals for review, rather than editing shared records directly.
- A separate offline recovery authority can authorize a replacement primary if the original host disappears. Authority conflicts and unavailable updates require explicit handling.
- Versioned JSON snapshots and change feeds share one model; neither is a raw WordPress database export.
- LibreTT Desktop imports records into its local database. Users can edit them locally; no desktop edits are uploaded to the online registry.
- Public datasets, media and private administrative data have different publication and backup requirements.

## Suggested first milestone

Create and manage an empty registry on one WordPress site, publish player/club profiles, and validate portable JSON export/import. Next add desktop integration, followed by verified replicas, proposals and recovery. The design accounts for later trust features; they are not present-day guarantees.

## Documentation

Start with the [documentation index](docs/en/INDEX.md).

- [Development plan](docs/en/PLAN.md)
- [Architecture](docs/en/ARCHITECTURE.md)
- [Data model and lifecycle](docs/en/DATA_MODEL.md)
- [Publication policy proposal](docs/en/PUBLICATION_POLICY.md)
- [Proposed protocol](docs/en/PROTOCOL.md)
- [Snapshot contract — draft 1](docs/en/SNAPSHOT_CONTRACT.md)
- [Desktop integration](docs/en/DESKTOP_INTEGRATION.md)
- [Trust and recovery](docs/en/TRUST_AND_RECOVERY.md)
- [Administrator workflows](docs/en/ADMIN_GUIDE.md)
- [Architecture decision](docs/adr/0001-registry-foundation.md)
- [Contributing](CONTRIBUTING.md), [security policy](SECURITY.md), [agent instructions](AGENTS.md)

Phase 0 documentation preparation is complete: [readiness and entry gates](docs/en/PHASE_0_READINESS.md), [schema/examples](docs/en/CONTRACT_VALIDATION.md), and [reviewed compatibility/tool targets](docs/en/COMPATIBILITY_AND_TOOLING.md). The first implementation slice and its tests are now recorded separately.

## Development

Composer dependencies and test configurations are committed. Follow [development setup](docs/en/DEVELOPMENT.md) on a disposable WordPress site; activation creates setup tables only and a separate administrator action creates the registry UUID. No production installer or release ZIP is published.

## Author and license

Copyright (C) 2026 Aleksa Dimitrijević.

Code and project documentation are licensed under the **GNU Affero General Public License, version 3 or any later version** (`AGPL-3.0-or-later`); see [LICENSE](LICENSE). Commercial redistribution is permitted subject to the license. No warranty is provided.

The software license does not automatically license player datasets, personal information, photographs or third-party material. Their permissions and publication terms must be specified separately. No registered trademark status is claimed.
