# PHP 8.3 hosting and development ZIP

[Srpski](../sr/PHP83_COMPATIBILITY.md)

The user approved adapting PHP requirements after installing a GitHub source ZIP on PHP 8.3.3 / WordPress 7.1.3. This record supplements earlier PHP 8.5/PHPUnit 13 results and does not imply a production release.

## Actual evidence

- PHP 8.3.3 (64-bit), WordPress 7.1.3 single-site, MariaDB 10.11.19/InnoDB, GD, isolated rootless development environment.
- PHPUnit 12.5.38: 47 unit tests / 80 assertions and 42 integration tests / 146 assertions passed on exact PHP 8.3.3.
- PHPStan maximum level on PHP 8.3.3: no source errors.
- PHP 8.5.11 regression: the same 47 unit / 42 integration tests passed with PHPUnit 12.5.38.
- Composer platform resolution is pinned to 8.3.3. Runtime package versions are unchanged; development PHPUnit/Symfony dependencies use maintained PHP-8.3-compatible versions. Update audit reported no known advisories.

The generated runtime-only ZIP passed actual extraction and activation on a fresh isolated WordPress 7.1.3 / PHP 8.3.3 site: 22 empty custom tables, administrator capabilities, next-request bootstrap, admin hooks, shortcode/API registration and explicit primary registry creation. Composer real-platform checks and packaged autoload passed. Runtime vendor contains seven packages and no development dependencies. The first verification attempt needed a test-harness fix to reload user capabilities after activation; the delivered plugin did not need a capability workaround.

PHP CS Fixer runs on PHP 8.3.3 and checks 122 source/test/tool files. Serbian activation messages were compiled with msgfmt --check. PHP source and both shell helpers pass syntax checks. The local ZIP and SHA-256 are under ignored build/; no release or external upload was performed.

## Installation

Install `build/librett-player-registry-development.zip` through WordPress Plugins → Add New → Upload Plugin. Confirm replacement of the previously installed source-only copy when prompted, then activate. The ZIP includes runtime-only Composer vendor, source, translations, schema and documentation. No Composer installation is needed on the hosting server. GitHub Code → Download ZIP still contains source only.

No new registry/data is created automatically. Create your own primary through LibreTT Registry, then use Players/Clubs. Photo/Custom document upload requires a private directory configured as described in the [administrator guide](ADMIN_GUIDE.md). Database/extensions/filesystem permissions are still hosting-specific requirements.

## MariaDB 11.8 hosting addition

The user reported `11.8.8-MariaDB-ubu2404`. The server gate now accepts MariaDB 10.11 or 11.8, while MySQL 8.4 and schema definitions/checksums are unchanged. A separate MariaDB 11.8.8 development container uses independent data/socket directories; it never upgrades the existing 10.11 data directory. On PHP 8.3.3 / WordPress 7.1.3 / exact `11.8.8-MariaDB-ubu2404`, all 42 integration tests / 146 assertions passed. The extracted ZIP also passed fresh activation, all 22 custom tables, administrator capabilities, next-request bootstrap, admin hooks, shortcode/API registration and explicit primary setup.

## Reproduce

```sh
LIBRETT_DEV_PHP=8.3.3 tools/dev/run build
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run db-start
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run wp-install
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
LIBRETT_DEV_PHP=8.3.3 tools/package-plugin
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run php tools/verify-plugin-package.php
```

Package verification creates a fresh synthetic database/site under ignored local/dev and does not contact the user's hosting. PHP 8.4 is accepted by the gate but was not executed here. The existing PHP 8.5 default development environment remains available. No WordPress <7.1.3, multisite, MySQL deployment, live browser acceptance or production performance guarantee is added.

[PHPUnit's maintained PHP compatibility](https://phpunit.de/supported-versions.html) and [PHP branch support](https://www.php.net/supported-versions.php) were rechecked. Suggested commit: `fix: support PHP 8.3 hosting and package runtime dependencies`.
