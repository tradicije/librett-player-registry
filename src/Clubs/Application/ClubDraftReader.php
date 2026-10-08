<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class ClubDraftReader
{
    public function __construct(private ClubDraftRepository $repository, private RegistryContextReader $registry) {}

    public function find(Actor $actor, EntityId $id): ?ClubDraft
    {
        $this->authorize($actor);
        return $this->repository->find($id);
    }

    /** @return list<ClubDraft> */
    public function search(Actor $actor, string $query, int $offset = 0): array
    {
        $this->authorize($actor);
        PlainText::assertValid($query, 200);
        if ($offset < 0 || $offset > 100000) {
            throw new RegistryFailure('resource_limit');
        }
        return $this->repository->search($query, $offset);
    }

    private function authorize(Actor $actor): void
    {
        $actor->assertCanEditProfiles();
        if ($this->registry->current() === null) {
            throw new RegistryFailure('registry_unconfigured');
        }
    }
}
