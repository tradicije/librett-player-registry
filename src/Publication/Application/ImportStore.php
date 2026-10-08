<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Publication\Domain\ImportJob;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface ImportStore
{
    /** @return array{local:EntityId,revision:int}|null */
    public function mapping(EntityId $registry, string $type, EntityId $source): ?array;
    public function stage(ImportJob $job): void;
    public function job(EntityId $id): ?ImportJob;
    /** @return array{actor_id:int,payload_hash:string,semantic_hash:string,players:int,clubs:int}|null */
    public function receipt(EntityId $request): ?array;
    /** @return array{players:int,clubs:int}|null */
    public function repeated(string $semanticHash): ?array;
    /** @return list<array{id:string,created_at:string}> */
    public function pending(int $actorId): array;
    public function cancel(EntityId $id, int $actorId): void;
    public function complete(ImportJob $job, EntityId $registry, int $players, int $clubs): void;
}
