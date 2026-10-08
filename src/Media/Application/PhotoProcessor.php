<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Application;

use LibreTT\PlayerRegistry\Media\Domain\PhotoAsset;

interface PhotoProcessor
{
    public function prepare(string $bytes, string $attribution, string $rightsEvidence): PhotoAsset;
}
