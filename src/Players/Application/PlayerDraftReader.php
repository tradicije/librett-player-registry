<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Application;

use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class PlayerDraftReader
{
    public function __construct(private PlayerDraftRepository $repository, private RegistryContextReader $registry) {}

    public function find(Actor $actor, EntityId $id): ?PlayerDraft
    {
        $this->authorize($actor);
        return $this->repository->find($id);
    }

    /** @return list<PlayerDraft> */
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
