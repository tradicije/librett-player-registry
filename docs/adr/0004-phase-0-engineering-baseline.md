# ADR 0004: Phase 0 engineering baseline and implementation gates

Date: 2026-10-07
Status: Accepted engineering direction for documentation completion; no runtime or production approval.

## Context

The user requested completion of preparation before Phase 1. Publication and snapshot proposals needed a structural schema, examples, ownership/transaction/migration decisions and reviewed upstream tooling. A documentation project cannot honestly deliver installed tool resolution, deployment tests or a crypto audit without implementation and authorization.

## Decision

Adopt ADR 0002 publication and ADR 0003 unsigned snapshot decisions as the Phase 0 engineering baseline, refined by the bundled v1 JSON Schema and mandatory parser/semantic contract. Approve PHP 8.5, WordPress 7.1.3 single-site and MariaDB 10.11 as the primary implementation target, with MySQL 8.4 as an alternate verification target. These are design choices, not a verified runtime minimum or deployment approval.

Specify acyclic module interfaces, private storage ownership, shared InnoDB unit of work, separate draft/public revisions, protected media and resumable migrations without DDL rollback promises. Select Composer, PHPStan, PHP CS Fixer, PHPUnit and UUID/schema adapter families. Bounded parser resolution is an explicit Phase 1 spike; JCS/crypto interoperability is a later gate. Do not install dependencies or create plugin code in this task.

Review later authenticated publication as a separate proposed contract: pinned root/claim continuity, Ed25519/JCS byte binding, offline authority transfer and conflict/replay/stale restore behavior. Do not extend unsigned snapshot v1 to claim authentication.

## Consequences

The [English readiness record](../en/PHASE_0_READINESS.md) / [srpska evidencija](../sr/PHASE_0_READINESS.md) closes documentation preparation and lists evidence required during implementation and before deployment. Dataset rights, lawful publication, minors and retention remain operator configuration requirements before real data. Normative signed formats/security fixtures remain gates before their feature phases. Exact dependency versions/commands and measured runtime support follow authorized installation/verification, not speculative documentation.

No plugin, API, installation claim, release, production dataset or security guarantee is delivered by this decision.
