<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final class UploadRequest
{
    public static function bytes(string $name, int $maximum): ?string
    {
        if ($maximum < 1 || $maximum > 33554432) {
            throw new RegistryFailure('resource_limit');
        }
        $file = $_FILES[$name] ?? null;
        if ($file === null || is_array($file) && ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!is_array($file) || ($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])) {
            throw new RegistryFailure('invalid_upload');
        }
        $bytes = file_get_contents($file['tmp_name'], false, null, 0, $maximum + 1);
        if ($bytes === false || strlen($bytes) > $maximum) {
            throw new RegistryFailure('resource_limit');
        }
        return $bytes;
    }
}
