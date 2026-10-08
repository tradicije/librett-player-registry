<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Application;

use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Application\EntityIdGenerator;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class SavePlayerDraft
{
    public function __construct(
        private PlayerDraftRepository $repository,
        private RegistryContextReader $registry,
        private UnitOfWork $transactions,
        private EntityIdGenerator $ids,
    ) {}

    public function execute(Actor $actor, ?EntityId $id, EditRevision $expected, PlayerData $data, DraftState $state): PlayerDraft
    {
        $actor->assertCanEditProfiles();
        if (($id === null) !== ($expected->value === 0)) {
            throw new RegistryFailure('revision_conflict');
        }
        if ($id === null && $state !== DraftState::Active) {
            throw new RegistryFailure('invalid_data');
        }
        $draft = new PlayerDraft($id ?? $this->ids->generate(), $expected->next(), $data, $state);
        $this->transactions->run(function () use ($draft, $expected, $actor): void {
            // current() rejects unsupported roles, including replica writes.
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $this->repository->save($draft, $expected, $actor->id);
        });
        return $draft;
    }
}
