<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class PlayerDraft
{
    public function __construct(
        public EntityId $id,
        public EditRevision $revision,
        public PlayerData $data,
        public DraftState $state,
    ) {
        if ($revision->value < 1) {
            throw new \InvalidArgumentException('Stored draft revision must be positive.');
        }
    }
}
