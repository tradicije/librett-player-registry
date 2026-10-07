<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Domain;

use InvalidArgumentException;

final readonly class Registry
{
    public string $name;

    public function __construct(public RegistryId $id, string $name)
    {
        if (!mb_check_encoding($name, 'UTF-8') || mb_strlen($name, 'UTF-8') > 200
            || preg_match('/\S/u', $name) !== 1 || preg_match('/[\x00-\x1f\x7f-\x9f]/u', $name) === 1) {
            throw new InvalidArgumentException('Registry name must be nonblank plain text up to 200 characters.');
        }
        $this->name = $name;
    }
}
