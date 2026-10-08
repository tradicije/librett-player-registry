<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Domain;

/** Administrative retention only; neither state authorizes publication. */
enum DraftState: string
{
    case Active = 'active';
    case Archived = 'archived';
}
