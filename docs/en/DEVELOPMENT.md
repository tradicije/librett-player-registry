# Development standards

[Srpski](../sr/DEVELOPMENT.md)

The first Phase 1 bootstrap is implemented. Read [implementation status](IMPLEMENTATION_STATUS.md), [architecture](ARCHITECTURE.md) and the [compatibility matrix](COMPATIBILITY_AND_TOOLING.md) before extending it. Exact dependencies are pinned in composer.lock; install from the lockfile rather than updating dependencies implicitly.

Use PHP 8.5 on a 64-bit runtime with the extensions declared in composer.json. The WordPress adapter also requires GD or Imagick. Run from the repository root:

```sh
composer install
php vendor/bin/phpunit
php vendor/bin/phpstan analyse --debug --memory-limit=512M
php vendor/bin/php-cs-fixer check --sequential
composer validate --strict --no-check-publish
```

PHPStan uses the maximum level. `--debug` runs sequentially and avoids worker socket restrictions in sandboxed environments. The formatter currently supports PER-CS 3.0; PER 3.1 remains the documented target and requires manual review of additions beyond that ruleset. Domain/application classes must remain loadable without WordPress.

## Disposable WordPress integration environment

Use a separate WordPress 7.1.3 directory and a private MariaDB 10.11 instance with a UNIX socket, networking disabled and a disposable root account with an empty password. This helper is only for that local test environment. Never point it at an existing user site or production database. MySQL 8.4 is a selected target but has not been exercised in this batch.

Set the following paths to your own disposable environment:

```sh
export LIBRETT_WP_ROOT=/path/to/disposable/wordpress
export LIBRETT_DB_SOCKET=/path/to/private/mysql.sock
export LIBRETT_TEST_DB=librett_registry_test_bootstrap
ln -s "$PWD" "$LIBRETT_WP_ROOT/wp-content/plugins/librett-player-registry"
php tools/install-integration-site.php
php vendor/bin/phpunit -c phpunit.integration.xml
```

The installer requires the `librett_registry_test_` database prefix and refuses to overwrite a different wp-config.php. It creates synthetic administrator credentials without printing the password; mail, external WordPress HTTP, cron and automatic updates are disabled. Integration tests require that dedicated database, create isolated table prefixes and remove their test tables. The lifecycle test also exercises the dedicated site's plugin tables. Do not use this environment as a deployable installation.

Unit and integration suites cover the implemented identity/bootstrap behavior; they do not verify planned player CRUD, publication, replication or recovery. No CI workflow or release packaging is implemented. [Implementation status](IMPLEMENTATION_STATUS.md) records actual versions, results and limits.

Preserve module boundaries, strict types, explicit ports and Composer PSR-4. A future scripted frontend uses strict TypeScript; no framework is selected. Future release ZIPs must include runtime dependencies and [third-party notices](../../THIRD_PARTY_NOTICES.md), while excluding development dependencies, tests, datasets and recovery secrets. No release version is assigned.

## Private-draft extension verification pending

The 2026-10-08 Players/Clubs extension has not been executed in this workspace, where PHP/Composer/msgfmt are unavailable. Earlier bootstrap results do not cover migration 002 or draft forms. Before deployment, authorized verification must cover permission/nonce denial, unconfigured/replica context, Unicode/control/field bounds, optional birth year, duplicate names, stale edits, revision exhaustion, audit rollback, archive/restore, bounded search, fresh/existing activation, migration interruption/checksum/future-schema/lock failures and retention of all seven tables. Existing bootstrap tests have not been changed or rerun.
