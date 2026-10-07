# Contributing

[Srpski](CONTRIBUTING-sr.md)

Read [README](README.md), [the plan](docs/en/PLAN.md), [architecture](docs/en/ARCHITECTURE.md) and [AGENTS.md](AGENTS.md). The first development bootstrap exists; follow [implemented scope](docs/en/IMPLEMENTATION_STATUS.md) and keep planned features separate.

Phase 0 engineering decisions and review limits are recorded in [readiness](docs/en/PHASE_0_READINESS.md). Public schema acceptance also requires [parser/semantic checks](docs/en/CONTRACT_VALIDATION.md); signed formats remain [separate later review gates](docs/en/AUTHENTICATED_PUBLICATION.md).

## Propose a change

Describe the user problem, current/planned behavior, concrete examples and failure cases. Separate confirmed decisions from open questions. Record substantial architectural changes in docs/adr/ before implementing them. Never advertise an unimplemented feature as working.

## Boundaries to preserve

- Any organization can create an empty independent registry; data is not shipped with the plugin.
- WordPress-specific functions stay in adapters. Stable identifiers and JSON contracts remain portable.
- Public profiles do not expose all stored fields by default.
- Desktop import is one-way. Local overrides and tournament snapshots survive refreshes.
- Replica writes are proposals subject to primary moderation; recovery transfers authority explicitly.
- AGPL permissions apply to software; user dataset/media permissions are separate.

## Changes and review

Preserve unrelated changes and keep patches focused. Update CHANGELOG.md and affected English/Serbian documentation. Use synthetic/anonymized fixtures; never commit federation databases, player photos without permission, credentials or recovery keys.

When verification is requested, cover valid/invalid imports, duplicates, migrations, interrupted synchronization, replay, authorization failures, deletion and recovery conflicts. Report actual commands/results and limitations. Actual tooling and commands are in the development guide.

User-facing text supports Serbian and English. Use stable translation keys; do not translate stored identifiers/states. Keep names as entered by their owners.

## Security reports

Use [SECURITY.md](SECURITY.md) for vulnerabilities; do not disclose secrets or personal records in public issues.

## License

Contributions must be compatible with AGPL-3.0-or-later. Preserve copyright and identify third-party sources/licenses. No copyright assignment or contributor agreement is currently required. Permission to contribute code does not authorize publication of third-party personal data.
