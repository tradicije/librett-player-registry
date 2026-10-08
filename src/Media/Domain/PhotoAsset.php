<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class PhotoAsset
{
    public function __construct(
        public EntityId $id,
        public string $key,
        public string $digest,
        public string $mime,
        public int $length,
        public int $width,
        public int $height,
        public string $attribution,
        public string $rightsEvidence,
    ) {
        PlainText::assertValid($attribution, 500, true);
        PlainText::assertValid($rightsEvidence, 1000, true);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || $length < 1 || $length > 5242880
            || $width < 1 || $height < 1 || $width > 4096 || $height > 4096
            || preg_match('/\A[0-9a-f]{64}\z/', $digest) !== 1) {
            throw new \InvalidArgumentException('Invalid photo descriptor.');
        }
    }
}
