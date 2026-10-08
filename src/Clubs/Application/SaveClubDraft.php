<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Application;

use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Application\EntityIdGenerator;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class SaveClubDraft
{
    public function __construct(
        private ClubDraftRepository $repository,
        private RegistryContextReader $registry,
        private UnitOfWork $transactions,
        private EntityIdGenerator $ids,
        private ?\LibreTT\PlayerRegistry\Shared\Application\ArchiveDraft $archive = null,
    ) {}

    public function execute(Actor $actor, ?EntityId $id, EditRevision $expected, ClubData $data, DraftState $state): ClubDraft
    {
        $actor->assertCanEditProfiles();
        if (($id === null) !== ($expected->value === 0)) {
            throw new RegistryFailure('revision_conflict');
        }
        if ($id === null && $state !== DraftState::Active) {
            throw new RegistryFailure('invalid_data');
        }
        $draft = new ClubDraft($id ?? $this->ids->generate(), $expected->next(), $data, $state);
        $this->transactions->run(function () use ($draft, $expected, $actor): void {
            // current() rejects unsupported roles, including replica writes.
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $this->repository->save($draft, $expected, $actor->id);
            if ($draft->state === DraftState::Archived) {
                $this->archive?->execute($actor, 'club', $draft->id);
            }
        });
        return $draft;
    }
}
