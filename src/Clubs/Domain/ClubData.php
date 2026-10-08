<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class ClubData
{
    public function __construct(
        public string $name = '',
        public string $country = '',
        public string $region = '',
        public string $abbreviation = '',
    ) {
        PlainText::assertValid($name, 200, true);
        PlainText::assertValid($country, 100, false);
        PlainText::assertValid($region, 100, false);
        PlainText::assertValid($abbreviation, 32, false);
    }
}
