<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Application;

use LibreTT\PlayerRegistry\Media\Domain\PhotoAsset;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface PhotoCatalogue
{
    public function forPlayer(EntityId $player): ?PhotoAsset;
    public function find(EntityId $id): ?PhotoAsset;
    public function attach(EntityId $player, ?PhotoAsset $photo, int $actorId): void;
    /** @return array<string, int|string> */
    public function publicDescriptor(PhotoAsset $photo): array;
}
