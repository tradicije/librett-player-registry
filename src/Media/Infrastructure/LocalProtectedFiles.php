<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use LibreTT\PlayerRegistry\Media\Application\ProtectedFiles;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use Ramsey\Uuid\Uuid;

final readonly class LocalProtectedFiles implements ProtectedFiles
{
    public function __construct(private string $configuredDirectory, private string $documentRoot, private ?string $wordpressRoot = null) {}

    private function directory(): string
    {
        $directory = realpath($this->configuredDirectory);
        $documentRoot = realpath($this->documentRoot);
        $wordpressRoot = $this->wordpressRoot === null ? false : realpath($this->wordpressRoot);
        $permissions = $directory === false ? false : fileperms($directory);
        if ($directory === false || $documentRoot === false || !is_dir($directory) || !is_writable($directory)
            || $directory === $documentRoot || str_starts_with($directory . DIRECTORY_SEPARATOR, $documentRoot . DIRECTORY_SEPARATOR)
            || ($wordpressRoot !== false && ($directory === $wordpressRoot || str_starts_with($directory . DIRECTORY_SEPARATOR, $wordpressRoot . DIRECTORY_SEPARATOR)))
            || $permissions === false || ($permissions & 0077) !== 0) {
            throw new RegistryFailure('protected_storage_unavailable');
        }
        return $directory;
    }

    public function put(string $bytes, string $extension): string
    {
        if (!in_array($extension, ['jpg', 'png', 'pdf', 'txt'], true) || strlen($bytes) > 5242880) {
            throw new RegistryFailure('resource_limit');
        }
        $key = Uuid::uuid4()->toString() . '.' . $extension;
        $path = $this->directory() . DIRECTORY_SEPARATOR . $key;
        $handle = fopen($path, 'xb');
        if ($handle === false) {
            throw new RegistryFailure('storage_failure');
        }
        try {
            if (!chmod($path, 0600) || fwrite($handle, $bytes) !== strlen($bytes) || !fflush($handle)) {
                throw new RegistryFailure('storage_failure');
            }
        } finally {
            fclose($handle);
        }
        return $key;
    }

    public function read(string $key, int $maximum): string
    {
        if (preg_match('/\A[0-9a-f-]{36}\.(?:jpg|png|pdf|txt)\z/', $key) !== 1 || $maximum < 1 || $maximum > 5242880) {
            throw new RegistryFailure('invalid_data');
        }
        $path = $this->directory() . DIRECTORY_SEPARATOR . $key;
        if (is_link($path) || !is_file($path)) {
            throw new RegistryFailure('media_unavailable');
        }
        $bytes = file_get_contents($path, false, null, 0, $maximum + 1);
        if ($bytes === false || strlen($bytes) > $maximum) {
            throw new RegistryFailure('resource_limit');
        }
        return $bytes;
    }
}
