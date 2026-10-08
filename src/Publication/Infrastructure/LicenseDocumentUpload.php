<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use finfo;
use LibreTT\PlayerRegistry\Media\Application\ProtectedFiles;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final readonly class LicenseDocumentUpload
{
    public function __construct(private ProtectedFiles $files) {}

    public function store(string $bytes): string
    {
        if ($bytes === '' || strlen($bytes) > 1048576) {
            throw new RegistryFailure('resource_limit');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if ($mime === 'application/pdf' && str_starts_with($bytes, '%PDF-')) {
            return $this->files->put($bytes, 'pdf');
        }
        if ($mime === 'text/plain' && mb_check_encoding($bytes, 'UTF-8') && !str_contains($bytes, "\0")) {
            return $this->files->put($bytes, 'txt');
        }
        throw new RegistryFailure('unsupported_license_document');
    }
}
