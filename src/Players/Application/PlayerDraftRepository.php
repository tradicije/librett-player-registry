<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Application;

use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface PlayerDraftRepository
{
    public function find(EntityId $id): ?PlayerDraft;

    /** @return list<PlayerDraft> */
    public function search(string $query, int $offset): array;

    /** Must compare the expected revision and write the private audit atomically. */
    public function save(PlayerDraft $draft, EditRevision $expected, int $actorId): void;
}
