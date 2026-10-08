<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface ClubAliases
{
    /** @return list<string> */
    public function forClub(EntityId $club): array;
    /** @param list<string> $aliases */
    public function replace(EntityId $club, array $aliases): void;
}
