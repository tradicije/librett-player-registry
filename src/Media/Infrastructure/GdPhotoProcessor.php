<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use finfo;
use LibreTT\PlayerRegistry\Media\Application\PhotoProcessor;
use LibreTT\PlayerRegistry\Media\Application\ProtectedFiles;
use LibreTT\PlayerRegistry\Media\Domain\PhotoAsset;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use Ramsey\Uuid\Uuid;

final readonly class GdPhotoProcessor implements PhotoProcessor
{
    public function __construct(private ProtectedFiles $files) {}

    public function prepare(string $bytes, string $attribution, string $rightsEvidence): PhotoAsset
    {
        if ($bytes === '' || strlen($bytes) > 5242880) {
            throw new RegistryFailure('resource_limit');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $size = @getimagesizefromstring($bytes);
        if ($size === false || !in_array($mime, ['image/jpeg','image/png'], true) || $size['mime'] !== $mime
            || $size[0] < 1 || $size[1] < 1 || $size[0] > 4096 || $size[1] > 4096) {
            throw new RegistryFailure('unsupported_photo');
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RegistryFailure('unsupported_photo');
        }
        ob_start();
        try {
            // Decode and encode a derivative: original metadata and unparsed trailing bytes are discarded.
            $ok = $mime === 'image/png' ? imagepng($image) : imagejpeg($image, null, 90);
            $derivative = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        if (!$ok || $derivative === false || strlen($derivative) > 5242880) {
            throw new RegistryFailure('resource_limit');
        }
        $key = $this->files->put($derivative, $mime === 'image/png' ? 'png' : 'jpg');
        return new PhotoAsset(new EntityId(Uuid::uuid4()->toString()), $key, hash('sha256', $derivative), $mime, strlen($derivative), $size[0], $size[1], $attribution, $rightsEvidence);
    }
}
