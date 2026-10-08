<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class SetClubAliases
{
    public function __construct(private ClubDraftRepository $clubs, private ClubAliases $aliases, private RegistryContextReader $registry, private UnitOfWork $transactions) {}

    /** @param list<string> $aliases */
    public function execute(Actor $actor, EntityId $id, EditRevision $expected, array $aliases): void
    {
        $actor->assertCanEditProfiles();
        if (count($aliases) > 20 || count(array_unique($aliases)) !== count($aliases)) {
            throw new RegistryFailure('invalid_data');
        }
        foreach ($aliases as $alias) {
            PlainText::assertValid($alias, 200, true);
        }
        $this->transactions->run(function () use ($actor, $id, $expected, $aliases): void {
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $club = $this->clubs->find($id);
            if ($club === null || $club->revision->value !== $expected->value) {
                throw new RegistryFailure('revision_conflict');
            }
            $this->clubs->save(new ClubDraft($id, $expected->next(), $club->data, $club->state), $expected, $actor->id);
            $this->aliases->replace($id, $aliases);
        });
    }
}
