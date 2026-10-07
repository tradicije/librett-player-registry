<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\UuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Domain\RegistryId;
use Ramsey\Uuid\Uuid;

final class RamseyUuidGenerator implements UuidGenerator
{
    public function generate(): RegistryId
    {
        return new RegistryId(Uuid::uuid4()->toString());
    }
}
