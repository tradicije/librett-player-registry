# ADR 0002: Explicit publication projection and review

Date: 2026-10-07
Status: Proposed; no implementation or legal authorization.

## Context

The first milestone needs public profiles and portable exports without exposing administrative records. Existing planning leaves the first catalogue scope and publication workflow open. Imported data and WordPress media URLs can expose personal information even when a profile is hidden.

## Proposed decision

Start with a public catalogue, protected administration and an explicit field allowlist. New/file-imported records are unpublished. Separate working records from approved public projections; publication requires authorized review of selected fields. Private edits do not change accepted public bytes until approved. Archive withdraws publication in the first milestone. Enforce the projection across pages, search, API, snapshots and media.

Keep authorization evidence private and version operator policy. Additional fields, minors and dataset/media redistribution require explicit organizational policy. Public catalogue access is not redistribution permission. Verified replicas will consume the accepted primary projection in a later milestone.

The bilingual [publication policy](../en/PUBLICATION_POLICY.md) / [politika objavljivanja](../sr/PUBLICATION_POLICY.md) specifies proposed fields, lifecycle and review scenarios.

## Consequences

Publication needs a distinct transaction, public revision/checkpoint semantics and protected media storage. A public projection must not disclose private edit counts. Draft staging adds storage and interface work but prevents an ordinary save or imported flag from publishing personal data. Withdrawal cannot remove already downloaded copies or alter historical tournament snapshots.

## Still open

Operator approval of catalogue scope and lawful publication policy, normative schema and limits, concrete capabilities, authorization evidence retention, media adapter/storage, revision model and safe import/restore behavior. This ADR does not complete Phase 0.
