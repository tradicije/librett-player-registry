<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class WithdrawProfile
{
    public function __construct(private PublicationStore $store, private UnitOfWork $transactions) {}

    public function execute(Actor $actor, string $type, EntityId $id, int $expectedPublic): void
    {
        $actor->assertCanPublish();
        if (!in_array($type, ['player', 'club', 'media'], true)) {
            throw new RegistryFailure('invalid_data');
        }
        $this->transactions->run(function () use ($actor, $type, $id, $expectedPublic): void {
            $this->store->lockForMutation();
            if ($this->store->revision($type, $id) !== $expectedPublic) {
                throw new RegistryFailure('revision_conflict');
            }
            $this->store->withdraw($type, $id, $actor->id);
        });
    }
}
