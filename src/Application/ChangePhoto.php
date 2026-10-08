<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Application;

use LibreTT\PlayerRegistry\Media\Application\PhotoCatalogue;
use LibreTT\PlayerRegistry\Media\Application\PhotoProcessor;
use LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision;
use LibreTT\PlayerRegistry\Players\Application\PlayerIdentityLookup;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ChangePhoto
{
    public function __construct(
        private PhotoProcessor $processor,
        private PhotoCatalogue $photos,
        private TouchPlayerDraftRevision $touch,
        private PlayerIdentityLookup $players,
        private RegistryContextReader $registry,
        private UnitOfWork $transactions,
    ) {}

    public function execute(Actor $actor, EntityId $player, EditRevision $expected, ?string $bytes, string $attribution, string $rights): void
    {
        $actor->assertCanEditProfiles();
        if ($this->registry->current() === null || !$this->players->isActive($player)) {
            throw new RegistryFailure('invalid_data');
        }
        $photo = $bytes === null ? null : $this->processor->prepare($bytes, $attribution, $rights);
        // A failed SQL commit can leave a private unreferenced derivative; it cannot expose it.
        $this->transactions->run(function () use ($actor, $player, $expected, $photo): void {
            $this->touch->execute($actor, $player, $expected);
            if (!$this->players->isActive($player)) {
                throw new RegistryFailure('invalid_data');
            }
            $this->photos->attach($player, $photo, $actor->id);
        });
    }
}
