<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Application;

use LibreTT\PlayerRegistry\Clubs\Application\MembershipRepository;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftRepository;
use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\Shared\Application\ArchiveDraft;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ArchiveRelations implements ArchiveDraft
{
    public function __construct(private MembershipRepository $memberships, private PlayerDraftRepository $players, private ?\LibreTT\PlayerRegistry\Publication\Application\PublicationStore $publications = null) {}

    public function execute(Actor $actor, string $type, EntityId $id): void
    {
        $this->publications?->withdraw($type, $id, $actor->id);
        if ($type !== 'club') {
            return;
        }
        foreach ($this->memberships->playersInClub($id) as $playerId) {
            $player = $this->players->find($playerId);
            if ($player !== null) {
                $this->players->save(new PlayerDraft($playerId, $player->revision->next(), $player->data, $player->state), $player->revision, $actor->id);
            }
        }
        $this->memberships->removeClub($id);
    }
}
