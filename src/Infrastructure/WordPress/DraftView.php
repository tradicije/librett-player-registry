<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class DraftView
{
    /** @param array<string, string> $fields */
    public function __construct(public EntityId $id, public EditRevision $revision, public DraftState $state, public array $fields) {}
}
