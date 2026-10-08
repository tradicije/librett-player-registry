<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

final readonly class SchemaSet implements SchemaMigration
{
    /** @param non-empty-list<SchemaMigration> $migrations */
    public function __construct(private array $migrations) {}

    public function install(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->install();
        }
    }

    public function assertComplete(): void
    {
        $this->migrations[array_key_last($this->migrations)]->assertComplete();
    }
}
