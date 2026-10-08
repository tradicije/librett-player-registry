<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ImportJob
{
    /** @param list<ImportMapping> $mappings */
    public function __construct(
        public EntityId $id,
        public int $actorId,
        public string $createdAt,
        public string $payload,
        public string $payloadHash,
        public string $semanticHash,
        public array $mappings,
    ) {}
}
