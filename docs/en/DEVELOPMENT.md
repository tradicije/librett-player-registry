# Development standards

[Srpski](../sr/DEVELOPMENT.md)

The first Phase 1 bootstrap is implemented. Read [implementation status](IMPLEMENTATION_STATUS.md), [architecture](ARCHITECTURE.md) and the [compatibility matrix](COMPATIBILITY_AND_TOOLING.md) before extending it. Exact dependencies are pinned in composer.lock; install from the lockfile rather than updating dependencies implicitly.

Use PHP 8.3.3–8.5.x on a 64-bit runtime with the extensions declared in composer.json. The WordPress adapter also requires GD. Run from the repository root:

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

Unit and integration suites cover identities, private CRUD, additive migrations, relationships, protected media, explicit publication, JSON validation/import and privacy. Replication/recovery remain absent. No CI workflow or production release is implemented; a development ZIP builder is available. [Implementation status](IMPLEMENTATION_STATUS.md) records actual versions, results and limits.

Preserve module boundaries, strict types, explicit ports and Composer PSR-4. A future scripted frontend uses strict TypeScript; no framework is selected. Future release ZIPs must include runtime dependencies and [third-party notices](../../THIRD_PARTY_NOTICES.md), while excluding development dependencies, tests, datasets and recovery secrets. No release version is assigned.

## Rootless Podman development tools

For this Fedora workstation, PHP/Composer/gettext are installed in a rootless Podman development image. Use `tools/dev/run` from the repository; PHP is not installed as a host-system executable. The image adds mysqli, intl, GD and ZIP to the official PHP 8.5 CLI image and copies Composer 2. Rootless Podman and host curl/tar are prerequisites.

```sh
tools/dev/run build
tools/dev/run composer install --no-interaction
tools/dev/run db-start
tools/dev/run wp-download
tools/dev/run wp-install
```

The wrapper mounts the repository at `/workspace`, the disposable WordPress tree at `/wordpress`, and a shared MariaDB UNIX socket. MariaDB 10.11 runs in `librett-registry-dev-db` without networking; data, downloads, cache and WordPress configuration stay in ignored `local/dev/`. PHP commands have networking disabled. Composer commands enable network access for dependency downloads; WordPress itself blocks external HTTP and mail. No host database/service is used.

When implementation verification is authorized, the installed tools can be called as follows:

```sh
tools/dev/run php vendor/bin/phpunit
tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
tools/dev/run php vendor/bin/phpstan analyse --debug --memory-limit=512M
tools/dev/run php vendor/bin/php-cs-fixer check --sequential
tools/dev/run msgfmt --check --output-file=local/dev/serbian.mo languages/librett-player-registry-sr_RS.po
```

Stop the disposable database with `tools/dev/run db-stop`; restart with `db-start`. Stopping preserves data. No automatic startup, purge or production deployment is configured. The image tags select the documented branches; record resolved versions on installation rather than treating tags as immutable patch pins.

## Catalogue verification

[Current verification and limits](VERIFICATION_2026_10_08.md) supersede earlier bootstrap-only coverage. Configure protected storage as described in the [administrator guide](ADMIN_GUIDE.md).
## PHP 8.3 and distributable development package

The user's PHP 8.3.3 hosting is an explicitly accepted compatibility target (ADR 0008). `config.platform.php=8.3.3` keeps dependency resolution compatible even when Composer runs on PHP 8.5; actual platform checks are still required. PHPUnit 12 replaces 13 so tests run on both PHP branches. Runtime packages are unchanged. Historical PHPUnit 13/PHP 8.5 verification remains historical evidence.

```sh
LIBRETT_DEV_PHP=8.3.3 tools/dev/run build
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
LIBRETT_DEV_PHP=8.3.3 tools/package-plugin
```

The builder copies allowlisted source/docs/languages, installs runtime-only dependencies from the lockfile in fresh ignored staging, checks the actual platform/autoload and creates a single-root ZIP plus SHA-256 in `build/`. It excludes development dependencies, tests, tools, repository metadata, local databases/media/configuration and recovery secrets. It does not mutate the working development vendor directory or publish anything. See [PHP 8.3 evidence](PHP83_COMPATIBILITY.md).

`LIBRETT_DEV_DB=11.8.8` selects the hosting database candidate with a separate named container, data directory and UNIX socket. Run db-start and wp-install with that variable before integration/package checks. The default 10.11 environment and its data remain intact.
