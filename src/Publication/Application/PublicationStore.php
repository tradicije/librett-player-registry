<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Publication\Domain\Policy;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

interface PublicationStore
{
    public function lockForMutation(): void;
    /** @return array{revision:int,policy:Policy}|null */
    public function policy(): ?array;
    public function savePolicy(Policy $policy, int $expected, int $actorId): void;
    /** @param array<string, mixed> $projection */
    public function approve(string $type, EntityId $id, int $expectedPublic, array $projection, string $evidence, int $actorId): void;
    /** Called only inside an authorized transaction. */
    public function withdraw(string $type, EntityId $id, int $actorId): void;
    public function revision(string $type, EntityId $id): int;
    /** @return array{checkpoint:string,players:list<array<string,mixed>>,clubs:list<array<string,mixed>>,memberships:list<array{player_id:string,club_id:string}>,media:list<array<string,mixed>>,tombstones:list<array<string,string>>} */
    public function snapshot(): array;
    /** @return array<string,mixed>|null */
    public function find(string $type, EntityId $id): ?array;
}
