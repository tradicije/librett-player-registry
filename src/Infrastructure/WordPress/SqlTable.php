<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

final readonly class SqlTable
{
    /**
     * @param array<string, string> $columns
     * @param list<string> $primary
     */
    public function __construct(public array $columns, public array $primary) {}
}
