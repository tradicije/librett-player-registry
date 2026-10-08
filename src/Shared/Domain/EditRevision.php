<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Domain;

use InvalidArgumentException;

final readonly class EditRevision
{
    public function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Invalid edit revision.');
        }
    }

    public function next(): self
    {
        if ($this->value === PHP_INT_MAX) {
            throw new InvalidArgumentException('Edit revision exhausted.');
        }
        return new self($this->value + 1);
    }
}
