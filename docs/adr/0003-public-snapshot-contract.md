# ADR 0003: Public snapshot types, revisions and staged imports

Date: 2026-10-07
Status: Proposed; schema approval and implementation remain pending.

## Context

Publication policy separates drafts from public projections. Portable files need exact types, bounded graph validation and identity rules before schema/tooling selection. Public snapshots cannot serve as complete organizational backups or proof of primary authority.

## Proposed decision

Use the bilingual [snapshot contract](../en/SNAPSHOT_CONTRACT.md) / [ugovor snimka](../sr/SNAPSHOT_CONTRACT.md) as draft 1 for review. Specify strict UTF-8 JSON, typed allowlists, decimal-string counters, complete projection arrays and minimal tombstones. Separate private edit revisions from public revisions and checkpoint. Apply publication dependency changes atomically.

The initial unsigned format has null authority generation and establishes no trusted authority. File imports into a primary stage unpublished drafts; independent registries allocate new identities with explicit provenance mappings. Desktop mapping retains remote identity separately. Signed replicas and private restore need separate contracts.

## Consequences

Bounded parsing must detect duplicate keys and enforce byte/depth/count limits before committing. Media descriptors do not trigger network requests. Stable identity mappings and transaction receipts are required for repeatable imports. Publication requires consistent export and dependent membership/media updates. Whole-snapshot limits may require a separately reviewed larger/paged format later.

## Open work

Review field choices and limits, approve normative JSON Schema and interoperability fixtures, define concrete storage/migrations and parser tooling, review the deployment matrix and authenticated publication/recovery contracts. No API, import implementation or tests are delivered by this ADR.
