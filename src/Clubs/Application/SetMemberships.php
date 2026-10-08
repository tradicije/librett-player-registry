<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Players\Application\PlayerIdentityLookup;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

/** Called in the transaction owned by the membership workflow. */
final readonly class SetMemberships
{
    public function __construct(private MembershipRepository $memberships, private PlayerIdentityLookup $players, private RegistryContextReader $registry) {}

    /** @param list<EntityId> $clubs */
    public function execute(Actor $actor, EntityId $player, array $clubs): void
    {
        $actor->assertCanEditProfiles();
        if ($this->registry->current() === null || !$this->players->isActive($player)) {
            throw new RegistryFailure('invalid_data');
        }
        if (count($clubs) > 100 || count(array_unique(array_map(static fn(EntityId $id): string => $id->value, $clubs))) !== count($clubs)) {
            throw new RegistryFailure('invalid_data');
        }
        usort($clubs, static fn(EntityId $a, EntityId $b): int => strcmp($a->value, $b->value));
        $this->memberships->replace($player, $clubs, $actor->id);
    }
}
