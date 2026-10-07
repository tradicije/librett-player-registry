# Development standards

[Srpski](../sr/DEVELOPMENT.md)

There is no code/toolchain to run yet. Read [architecture](ARCHITECTURE.md) for mandatory module boundaries and [plan](PLAN.md) for milestones.

Phase 0 has selected the [compatibility matrix and tooling families](COMPATIBILITY_AND_TOOLING.md). Exact resolved versions/configuration/check commands follow authorized installation in Phase 1. See [readiness](PHASE_0_READINESS.md) for actual review limits.

At design date (2026-10-07), PHP's supported-version page lists 8.5 as the newest supported branch. Target modern PHP 8.5 development; approve a WordPress/PHP/database matrix before claiming deployability. PHP-FIG PER Coding Style 3.1 replaces/extends PSR-12; use Composer PSR-4, strict types and explicit types in independent modules.

The maintained static-analysis, formatting, test and audit tool families are selected in the Phase 0 matrix. Document exact installation/check/build commands only when real configuration exists. If a frontend is introduced, use strict TypeScript and bounded view/state/transport modules; framework is undecided.

Domain/application packages must be loadable without bootstrapping WordPress. Adapter integration tests require WordPress separately. Planned CI checks dependency direction, syntax/style/static analysis, unit/adapter tests, migrations, exports, recovery fixtures and package contents. Tests require task authorization under AGENTS.md; none were added/run in this documentation bootstrap.

Release ZIPs will contain runtime code, required runtime dependencies and notices, not development secrets, tests, player datasets or recovery material. Do not choose a published version or tag before implementation starts.

References: [PHP support](https://www.php.net/supported-versions.php), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [PSR-4](https://www.php-fig.org/psr/psr-4/).
