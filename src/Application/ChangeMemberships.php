<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Application;

use LibreTT\PlayerRegistry\Clubs\Application\SetMemberships;
use LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ChangeMemberships
{
    public function __construct(private UnitOfWork $transactions, private TouchPlayerDraftRevision $touch, private SetMemberships $set) {}

    /** @param list<EntityId> $clubs */
    public function execute(Actor $actor, EntityId $player, EditRevision $expected, array $clubs): void
    {
        $actor->assertCanEditProfiles();
        $this->transactions->run(function () use ($actor, $player, $expected, $clubs): void {
            $this->touch->execute($actor, $player, $expected);
            $this->set->execute($actor, $player, $clubs);
        });
    }
}
