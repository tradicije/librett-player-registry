<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Application;

use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

/** Called inside the workflow's shared unit of work. */
final readonly class TouchPlayerDraftRevision
{
    public function __construct(private PlayerDraftRepository $repository) {}

    public function execute(Actor $actor, EntityId $id, EditRevision $expected): void
    {
        $actor->assertCanEditProfiles();
        $draft = $this->repository->find($id);
        if ($draft === null || $draft->revision->value !== $expected->value) {
            throw new RegistryFailure('revision_conflict');
        }
        $this->repository->save(new PlayerDraft($id, $expected->next(), $draft->data, $draft->state), $expected, $actor->id);
    }
}
