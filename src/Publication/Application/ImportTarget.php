<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Publication\Domain\ImportMapping;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use stdClass;

/** @phpstan-import-type Snapshot from SnapshotValidator */
interface ImportTarget
{
    public function revision(string $type, EntityId $id): ?int;
    /**
     * @param Snapshot $snapshot
     * @param list<ImportMapping> $mappings
     * @return array{players:int,clubs:int}
     */
    public function apply(Actor $actor, stdClass $snapshot, array $mappings): array;
}
