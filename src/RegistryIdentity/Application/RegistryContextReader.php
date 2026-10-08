<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Domain\Registry;

interface RegistryContextReader
{
    /** Returns a configured primary; unsupported roles must fail explicitly. */
    public function current(): ?Registry;
}
