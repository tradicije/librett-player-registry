<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final class Preflight
{
    public function assertReady(string $phpVersion = PHP_VERSION, int $integerSize = PHP_INT_SIZE): void
    {
        global $wp_version;
        if (version_compare($phpVersion, '8.3.3', '<') || version_compare($phpVersion, '8.6.0', '>=') || $integerSize !== 8 || !is_string($wp_version) || version_compare($wp_version, '7.1.3', '<') || version_compare($wp_version, '7.2', '>=')) {
            throw new RegistryFailure('unsupported_runtime');
        }
        if (is_multisite()) {
            throw new RegistryFailure('unsupported_multisite');
        }
        foreach (['mysqli', 'mbstring', 'intl', 'fileinfo'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new RegistryFailure('missing_extension');
            }
        }
        if (!extension_loaded('gd')) {
            throw new RegistryFailure('missing_image_backend');
        }
    }
}
