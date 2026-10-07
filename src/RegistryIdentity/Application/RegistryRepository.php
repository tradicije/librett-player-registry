<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Domain\Registry;

interface RegistryRepository
{
    public function current(): ?Registry;
    public function createIfUnconfigured(Registry $registry, int $actorId): void;
}
