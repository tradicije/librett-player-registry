<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface ClubDraftRepository
{
    public function find(EntityId $id): ?ClubDraft;

    /** @return list<ClubDraft> */
    public function search(string $query, int $offset): array;

    /** Must compare the expected revision and write the private audit atomically. */
    public function save(ClubDraft $draft, EditRevision $expected, int $actorId): void;
}
