<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\Shared\Application\EntityIdGenerator;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use Ramsey\Uuid\Uuid;

final class RamseyEntityIdGenerator implements EntityIdGenerator
{
    public function generate(): EntityId
    {
        return new EntityId(Uuid::uuid4()->toString());
    }
}
