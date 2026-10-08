<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ImportMapping
{
    public function __construct(public string $type, public EntityId $source, public EntityId $local, public int $expected, public ?EntityId $previousLocal = null)
    {
        if (!in_array($type, ['player','club'], true) || $expected < 0) {
            throw new \InvalidArgumentException('Invalid import mapping.');
        }
    }
}
