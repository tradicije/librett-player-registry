<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use LibreTT\PlayerRegistry\Publication\Application\Clock;

final class SystemClock implements Clock
{
    public function utcTimestamp(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
}
