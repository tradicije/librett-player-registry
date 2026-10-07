# Compatibility matrix and tooling selection

[Srpski](../sr/COMPATIBILITY_AND_TOOLING.md)

Phase 1 update: the [implemented bootstrap](IMPLEMENTATION_STATUS.md) records installed versions and actual tests. The remaining contracts below are planned; Phase 0 review statements refer to that historical documentation task.

Reviewed: 2026-10-07. Status: selected engineering targets for review and implementation, not tested plugin support or an advertised runtime minimum. Bootstrap dependencies are installed and locked; actual evidence is in implementation status.

## Upstream evidence

[PHP support](https://www.php.net/supported-versions.php) lists 8.5 as actively supported. [WordPress download](https://wordpress.org/download/) offers 7.1.3; its [PHP matrix](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) lists 7.1 with PHP 8.5. These facts describe upstream, not our plugin. Recheck patch releases at implementation time and record exact versions.

[WordPress hosting requirements](https://wordpress.org/about/requirements/) recommend MariaDB 10.11+ or MySQL 8.0+. We select narrower transactional targets below, without claiming older engines or all later versions work. [MariaDB maintenance](https://mariadb.org/about/#maintenance-policy) and [MySQL 8.4 DDL semantics](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html) must be reviewed for the concrete server build. Schema migrations cannot assume DDL rolls back.

## Candidate deployment verification matrix

| Target | WordPress | PHP | Database | Role / evidence |
| --- | --- | --- | --- | --- |
| A | 7.1.3 single-site | Latest stable 8.5 patch, 64-bit | MariaDB 10.11 latest maintained patch, InnoDB | Primary local development and first deployment candidate; upstream compatible, plugin untested |
| B | Same | Same | MySQL 8.4 latest maintained patch, InnoDB | Required alternate database verification before claiming MySQL support; plugin untested |

No PHP <8.5 compatibility effort, prerelease PHP, SQLite WordPress adapter, multisite/network activation or replicas in Phase 1. Use HTTPS and a Linux deployment reference; developers may use macOS/Windows containers, but host-native deployment support requires separate evidence. Document exact server build, SQL modes, collation, filesystem/media permissions, web server, memory/upload/body limits and all installed plugins/theme before deployment approval.

Candidate PHP extensions: `mysqli`, `mbstring`, `intl`, `fileinfo`, and one verified GD/Imagick image backend; JSON/hash support must be available. Image limits and protected storage are mandatory. `sodium` is required only when the later signing/recovery functionality is implemented. No UI can silently fall back to unsigned verification when crypto is absent. WordPress network routing and media backends need adapter-specific checks. Initial image input/output accepts JPEG/PNG only. Local imports require no outgoing network access.

## Selected tools

| Purpose | Selection / reviewed upstream | Implementation decision |
| --- | --- | --- |
| Autoload/dependency management | Composer 2, current download 2.10.3 ([source](https://getcomposer.org/download/)) | PSR-4, committed lockfile when dependencies are installed; no Composer plugins/scripts enabled without need |
| Static analysis | PHPStan 2, reviewed 2.3.0 ([release](https://github.com/phpstan/phpstan/releases/tag/2.3.0)) | Strictest practical level for core, typed adapter stubs; no blanket ignored errors |
| Formatting | PHP CS Fixer 3, reviewed 3.95.27 ([release](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/tag/v3.95.27)) | PER Coding Style 3.1 ([standard](https://www.php-fig.org/per/coding-style/)); pin explicit rules supported by installed fixer, review uncovered rules |
| Core/use-case tests when authorized | PHPUnit 13 ([support table](https://phpunit.de/supported-versions.html)) | Isolated core suite on PHP 8.5; exact patch pinned at install |
| WordPress adapter verification when authorized | PHPUnit 13 driving an isolated bootstrapped WordPress integration harness | Do not load WordPress `WP_UnitTestCase` into PHPUnit 13: [upstream WordPress test matrix](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/) still lists PHPUnit 9. Avoid adding an obsolete runner to the project; use public WordPress bootstrap/API and disposable database |
| Dependency audit | Composer audit ([CLI](https://getcomposer.org/doc/03-cli.md#audit)) | Audit locked runtime/dev packages and retain license notices |
| UUID adapter | ramsey/uuid maintained stable line ([manual](https://uuid.ramsey.dev/en/stable/)) | Generate v4 through a port; exact compatible release selected/pinned by resolver before use |
| Snapshot schema adapter | opis/json-schema 2 ([manual](https://opis.io/json-schema/2.x/)) | Draft 2020-12, bundled schemas, explicit formats; no Opis-specific schema extensions |
| Bounded JSON input | A maintained streaming/token parser behind `BoundedJsonReader` | Parser choice is an implementation spike: duplicate-key, byte/token/depth/count limits and PHP 8.5 compatibility are acceptance requirements. Do not call ordinary `json_decode` on unrestricted input or handwrite a replacement parser |
| Signing later | PHP sodium maintained Ed25519 implementation ([manual](https://www.php.net/manual/en/book.sodium.php)) | No signature algorithms implemented by the project; JCS provider requires separate interoperability review |

A parser and JCS package cannot be responsibly declared verified before exercising the required capabilities. Their spikes are explicitly scheduled in the relevant implementation milestone; no unverified package is added simply to close a checklist. This is a design/tool choice boundary, not omitted runtime verification. Avoid a dependency-injection framework, ORM, SPA or additional coding-standard stack in the initial milestone.

At implementation bootstrap, resolve supported stable releases against PHP 8.5, review license/security/maintenance, pin exact versions in lockfiles, record actual commands and versions, then run only authorized checks. The Phase 0 documents intentionally provide no fictitious build/test commands. No deployment matrix row is marked tested or operator-approved yet.
