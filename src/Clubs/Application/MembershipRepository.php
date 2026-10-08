<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface MembershipRepository
{
    /** @return list<EntityId> */
    public function forPlayer(EntityId $player): array;
    /** @param list<EntityId> $clubs */
    public function replace(EntityId $player, array $clubs, int $actorId): void;
    /** @return list<EntityId> */
    public function playersInClub(EntityId $club): array;
    public function removeClub(EntityId $club): void;
}
