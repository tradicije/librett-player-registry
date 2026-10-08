# ADR 0008: PHP 8.3 hosting and complete development package

Date: 2026-10-08. Status: Accepted at the user's request.

The user downloaded GitHub's source ZIP and attempted activation on PHP 8.3.3 / WordPress 7.1.3. The source ZIP excludes Composer vendor, and the previous bootstrap gate required PHP 8.5. The user explicitly authorized PHP 8.3 adaptation and a prepared plugin ZIP.

Accept 64-bit PHP 8.3.3–8.5.x; retain WordPress 7.1.3–7.1.x single-site and InnoDB database requirements. The user subsequently identified MariaDB 11.8.8; explicitly add the 11.8 branch alongside 10.11, verify with isolated data/socket storage and preserve all existing migration definitions/checksums. Pin Composer platform resolution to 8.3.3 so newer build hosts cannot silently select incompatible libraries. PHPUnit 12 is maintained and runs on PHP 8.3; source/runtime packages need no polyfill changes. Verify the exact user's patch in an isolated image, plus regressions on the existing PHP 8.5 environment.

Package fresh allowlisted source/docs/languages and locked runtime-only vendor in one librett-player-registry ZIP root. Validate real platform/autoload and the extracted package before delivery. Development/test/database/media/local configuration and recovery material never enter the ZIP. Separate PHP incompatibility from missing-vendor errors. Do not create a production version, publish or alter live user databases.

Historical tool/runtime records remain intact; current evidence and installation instructions supersede earlier PHP-8.5-only/no-package decisions. PHP 8.4 acceptance is not an actual PHP 8.4 execution claim. Hosting database/extensions/filesystem remain operator-specific checks.

Sources: https://phpunit.de/supported-versions.html and https://www.php.net/supported-versions.php (reviewed for this task).
