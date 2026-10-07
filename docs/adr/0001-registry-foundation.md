# ADR 0001: Independent player registry with a WordPress adapter

Date: 2026-10-07
Status: Accepted product direction; protocol and implementation details are proposed.

## Context

LibreTT Desktop needs an optional remote player/club catalogue while retaining editable offline local profiles. Organizations in different countries need independent datasets. Public sites need detailed player profiles, and a shared dataset may be mirrored or moved between domains. Loss of the original host must not make the registry permanently unusable.

## Decision

Deliver one plugin, LibreTT Player Registry, with setup choices to create an empty independent primary or connect a replica. Separate domain/application logic from WordPress, custom-table persistence, HTTP and media adapters. No live player records are part of the package.

Use stable registry/entity identifiers and a versioned JSON contract. Primary administrators edit accepted records; replicas publish authenticated projections and submit moderated proposals. Desktop imports are one-way and preserve local overrides/history.

Design separate offline recovery and online operational signing authorities. Authority transfer needs authenticated, replay-resistant claims, conflict handling and a bootstrap trust path; these are requirements, not implemented guarantees. No multisite leader election or concurrent multi-primary merging is in the initial scope.

## Consequences

Custom tables require explicit migrations, export/import and lifecycle management. WordPress public routing is needed without CPT dependency. The portable core is initially PHP; other hosting platforms require adapters, not merely a URL switch.

Changing a URL must not change registry identity. Copying data does not prove authority. Repositories and schema revisions must support transactional updates, idempotence and bounded parsing. Dataset publication/privacy terms are separate from AGPL software licensing.

## Open decisions

Minimum runtime versions, precise schema/API fields, trust-envelope canonicalization and key management, media transport, public/private catalog authorization, revision retention and registry handover conflict policy require review before implementation.
