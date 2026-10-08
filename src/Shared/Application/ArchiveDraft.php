<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface ArchiveDraft
{
    /** Runs inside the saving use case's transaction. */
    public function execute(Actor $actor, string $type, EntityId $id): void;
}
