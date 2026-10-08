<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Application;

interface ProtectedFiles
{
    public function put(string $bytes, string $extension): string;
    public function read(string $key, int $maximum): string;
}
