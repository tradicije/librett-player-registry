# Phase 0 completion and Phase 1 entry

[Srpski](../sr/PHASE_0_READINESS.md)

Phase 1 update: the [implemented bootstrap](IMPLEMENTATION_STATUS.md) records installed versions and actual tests. The remaining contracts below are planned; Phase 0 review statements refer to that historical documentation task.

Date: 2026-10-07. Status: documentation and engineering design baseline complete for the user's request to finish preparation before Phase 1. This is design review, not runtime, interoperability execution, security audit or production approval. No plugin code, dependency installation, release or live dataset is included.

## Decisions and artifacts

| Phase 0 requirement | Recorded result |
| --- | --- |
| Catalogue/publication | Public first catalogue, approved allowlist, private drafts/evidence, explicit publication and withdrawal: [policy](PUBLICATION_POLICY.md), ADR 0002 |
| Module boundaries | Named public interfaces, table ownership, acyclic dependency graph, injected infrastructure and capability checks: [module contracts](MODULE_CONTRACTS.md) |
| Public schema/identity | Unsigned v1 JSON Schema, stable typed UUIDs, decimal counters, omission semantics, limits and staged independent import mappings: [snapshot](SNAPSHOT_CONTRACT.md), [validation](CONTRACT_VALIDATION.md), ADR 0003 |
| Transaction/storage lifecycle | InnoDB unit of work, distinct private/public revisions, dependent public removals, import receipts, protected media and resumable DDL/migration restore: [storage](STORAGE_AND_MIGRATIONS.md) |
| Runtime/tools | Reviewed upstream sources; PHP 8.5 / WordPress 7.1.3 / MariaDB 10.11 primary target and MySQL 8.4 alternate; Composer/PHPStan/PHP CS Fixer/PHPUnit choices: [matrix](COMPATIBILITY_AND_TOOLING.md) |
| Trust/recovery review | Byte binding, pinned bootstrap, separate roots/operational keys, explicit chain/rotation/conflict/replay and stale restore handling: [authenticated publication design](AUTHENTICATED_PUBLICATION.md) |
| Documentation | English/Serbian counterparts, contract examples, ADR 0004, updated indexes and changelog |

The matrix is approved as an engineering target for implementation in this design baseline, not as a tested deployment/support promise. Exact patch builds, extension availability and hosting approval must be evidenced before deployment. Schema is the structural contract for unsigned public v1; mandatory parser/semantic rules in the companion documents are equally part of the contract. Authenticated formats remain proposed and unavailable until their later review gates.

## Explicit scope decisions

No private catalogue, multisite, historical membership/achievement model, player accounts or SPA in Phase 1. Independent imported registries get new identities and mappings; a public snapshot is never an organizational restore. Source tombstones are review information, not automatic primary deletion. Only approved projections are public. Real-data authorization, minors policy, rights and operator retention configuration are deployment inputs, not invented default permissions.

The first implementation task must stay with synthetic data until publication policy is configured. It starts with runtime/dependency bootstrap and empty migrations/setup, then follows the slices in module contracts. No Phase 1 feature was implemented while closing this phase.

## Implementation-time gates

| When | Required evidence |
| --- | --- |
| Phase 1 bootstrap | Actual runtime/database/extension versions, compatible maintained dependency resolution, lockfile/licenses, bounded-parser spike; fail if parser cannot reject duplicate keys or enforce all limits |
| Phase 1 authorized verification | Schema meta-validation and format assertions; execute accepted/rejected examples through parser/schema/semantics; permissions, publication/withdrawal, concurrent edits, all-or-nothing imports, media/migration failures |
| Before real data/public deployment | Operator publication/retention/minor policy, dataset/photo rights and protected delivery, tested matrix rows, actual support declaration and release/security workflow |
| Before Phase 2 | Desktop-side mapping/local override implementation and historical snapshot preservation cases in its repository |
| Before Phase 3 | Separate normative signed schema, maintained JCS provider, reference cryptographic fixtures, chain/conflict/feed tests and independent security review |
| Before Phase 5 | Offline signing/recovery UX, private restore drill, root rotation, concurrent handover and stale-data reconciliation review |

No install/build/check commands are claimed until configuration exists. The user instruction authorizes this documentation work, not dependency installation, implementation test execution, publication, commit or push. Test cases below are requirements for a separately authorized implementation task.

## Failure and migration cases to carry forward

Bounded bytes/depth/count/string tokens; invalid UTF-8/duplicate keys; counter precision/overflow; unknown/private/null fields; duplicate typed IDs/slugs/membership pairs; dangling media/club references; live/tombstone collisions; future checkpoints; policy drift; forged unsigned authority; same names/different people; stale preview; replayed receipts/different payload; stopped import before/after commit; denied capabilities/nonces; no public draft leakage; shared-media withdrawal; media finalization/unlink failure; failed preflight; parallel setup/checkpoint; migration restart/checksum mismatch; DDL partial completion; insufficient backup/disk space; old checkpoint restoration; explicit uninstall retention. Trust failure cases are in the authenticated design.

## Actual review and limits

Reviewed document consistency, source-linked upstream choices, schema properties/local references and synthetic example content. Documentation checks found no missing local Markdown link targets, all 31 schema references resolved locally, all 12 JSON artifacts were readable (including the intentional duplicate-key example), and English/Serbian document filename sets matched. These are documentation checks only. No JSON Schema validator is installed in the current workspace; schema meta-validation and fixture execution are not reported as passed. No WordPress/PHP/database or cryptographic test was run. No runtime minimum, signing interoperability, measured capacity or legal compliance is claimed.
