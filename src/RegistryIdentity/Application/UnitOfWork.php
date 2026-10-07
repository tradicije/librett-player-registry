<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Application;

interface UnitOfWork
{
    /** @param callable(): void $operation */
    public function run(callable $operation): void;
}
