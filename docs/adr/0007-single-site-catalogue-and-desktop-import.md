# ADR 0007: Complete single-site catalogue and one-way Desktop import

Date: 2026-10-08
Status: Accepted implementation direction at the user's request; verification recorded separately.

## Scope

The user authorized all six next steps: verify/fix private drafts; memberships/aliases/duplicate review; dataset-license and publication settings; protected photographs and explicit public approval; public search/API and bounded JSON import/export; one-way Desktop import. Replicas, proposals, signing/recovery and production release remain outside this task.

## Boundaries

Keep existing migration definitions/checksums immutable. Add feature schema through resumable, verified additive migration with operator backup acknowledgement for configured installations. Each module owns its data; membership/photo coordinators call public ports and share one transaction. Players never imports Clubs or Media. Changing relationships advances player private revisions. Public projection is an immutable approved copy; dependencies, checkpoint/events, withdrawal and audit commit together. Policy changes invalidate affected approvals and remove public projections atomically; a broader policy never publishes a private field automatically.

Store uploaded images and custom license documents outside the web document root. Deliver public derivatives only while referenced by approved projections; deliver selected license documents as downloads with nosniff. Validate size, type and decoded dimensions before committing; failures never publish partial bytes. Publication requires a configured purpose/terms, explicit field approval and private authorization references, plus configured minor handling and individual status review.

Unsigned snapshot v1 stays separate from authority/authentication. Bounded parsing rejects duplicate keys/invalid Unicode/limits before schema and graph validation. Imports are staged privately with explicit source-to-local mappings and receipt idempotence; every imported record starts unpublished. Equal names are review candidates and never automatic identity merges. Public endpoints provide approved projections only.

Desktop retains source UUIDs separately from local IDs, previews changes, preserves explicit local overrides/absent optional fields and historical registration snapshots, and records upstream withdrawal without deleting local profiles. No write/proposal channel is implemented in the Desktop client. HTTP and photo fetches are explicit, bounded and reject SSRF/redirect abuse. File import uses the same snapshot validator. Missing birth years require completion before a local player is saved.

## Evidence

Tests and bilingual implementation status must distinguish completed code from actual verification. No production ZIP/version or real-data authorization is created by this request. Operator data/media rights and deployment policy remain explicit inputs.
