# ADR 0006: Private player/club draft slice and additive migration

Date: 2026-10-08
Status: Implemented in development source; runtime verification pending, unreleased.

## Decision

Continue Phase 1 with separate Players and Clubs modules for private create/search/edit/archive/restore. Use injected registry-context, UUID, repository and transaction ports. Share only small identity/revision/text/retention value types and a reusable WordPress form view. Each module owns its rows and audit. Application readers/commands require the edit-profiles grant and configured primary; WordPress form writes additionally require POST, capability and nonce. Compare expected private revision and commit draft/audit together. Duplicate names remain independent UUIDs. No publication mutation or public counter exists in this slice.

Add migration 002 without changing migration 001's definitions/checksum. New unconfigured activation creates both schemas; a configured registry installs/resumes 002 through an explicit settings-capability/nonce action after acknowledging a verified operator backup and matching code. Use the existing connection-scoped lock, checksums, durable started/completed state and complete table verification. Failed/incomplete draft schema prevents draft administration. This is an additive migration, not a general restore/upgrade framework.

Archive retains UUID and fields; selecting Active explicitly restores the private draft. Publication-aware withdrawal, memberships, club aliases, explicit duplicate/source mappings, media and purge belong to later slices. Every accepted save advances private revision and writes an audit entry, even for identical submitted values. Optional player birth year is null in the domain and uses a private SQL zero sentinel; public exports must never expose it as a year.

## Dataset licensing requirement

The user requested a future wp-admin dataset-license setting with ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved and Custom (license URL or uploaded document). No automatic license selection. This is separate from AGPL code licensing, personal-data authorization and media rights. License settings/upload are documented requirements, not implemented features. The public v1 schema remains unchanged; a future reviewed publication adapter must resolve the selected terms to its existing dataset_terms_url contract.

## Verification and consequences

PHP/Composer/msgfmt are unavailable in the current Linux workspace. No runtime/test/static-analysis/formatter/database/browser claim is made for this extension. Original bootstrap tests/results do not verify it; failures/concurrency/migration/retention and translated forms require authorized verification before deployment. No dependencies, release, production database, commit or push are added by this task.
