# Security policy

[Srpski](SECURITY-sr.md)

Phase 0 engineering decisions and review limits are recorded in [readiness](docs/en/PHASE_0_READINESS.md). Public schema acceptance also requires [parser/semantic checks](docs/en/CONTRACT_VALIDATION.md); signed formats remain [separate later review gates](docs/en/AUTHENTICATED_PUBLICATION.md).

## Supported versions

There is no executable release or supported production version yet. The repository contains an unreleased identity bootstrap plus proposed later features; it is not security audited. Before any release, select supported runtimes, implement the threat-model requirements and publish the actual support matrix.

## Reporting

If this project is hosted on GitHub and private vulnerability reporting is enabled, use its Security tab to report privately. This file does not enable that setting. If no private channel is available, contact the maintainer or open a minimal public request for a private channel without exploit details or personal data. No private email address or response-time commitment has been established.

Include affected component/version, reproduction using synthetic data, impact and a suggested fix if known. Never include recovery secrets, passwords, full player databases or non-public photographs. Do not attack third-party deployments.

## Planned trust boundaries

Read [trust and recovery](docs/en/TRUST_AND_RECOVERY.md). Treat WordPress hosting, admin sessions, external registries, JSON/media imports, replicas and desktop imports as separate boundaries. A compromised host can bypass its local controls; signed authority checks protect cooperating clients, not the hosting owner's filesystem from its owner.

Public exports contain only the publication projection. Administrative exports/backups contain different private material and need restricted access. The offline recovery authority is never mirrored or stored in public records; operational server keys are protected separately.

Imports require schema/semantic validation, resource limits and transactional application. Network fetching requires SSRF/redirect/DNS defenses, TLS validation and size/time limits. Signature verification does not make content safe to render or make an old snapshot fresh.

Permissions are enforced on the server. WordPress nonces are not a substitute for capability checks. Recovery must not depend on a still-live original server and must detect stale/replayed/conflicting authority claims. Do not implement custom cryptography.

## Privacy and disclosure

Player records and photos may be personal data, including minors' information. Define what is collected, why, who may publish it, and what public replicas may redistribute before deployment. Public replication cannot promise removal of every downloaded copy. Publishing an archival copy must not bypass a withdrawal/purge policy.

Deletion, retention, redaction and lawful dataset use need organizational review before real data is imported. AGPL does not supply a legal basis for personal-data publication. This document provides engineering requirements, not a determination of legal compliance.
