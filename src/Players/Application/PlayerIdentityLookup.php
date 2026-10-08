<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Application;

use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface PlayerIdentityLookup
{
    public function isActive(EntityId $id): bool;
}
