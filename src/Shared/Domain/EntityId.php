<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Domain;

use InvalidArgumentException;

final readonly class EntityId
{
    public function __construct(public string $value)
    {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $value) !== 1) {
            throw new InvalidArgumentException('Entity identity must be a canonical RFC-variant UUID.');
        }
    }
}
