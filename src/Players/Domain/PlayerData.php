<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class PlayerData
{
    public function __construct(
        public string $name = '',
        public string $country = '',
        public string $region = '',
        public string $givenName = '',
        public string $familyName = '',
        public string $biography = '',
        public ?int $birthYear = null,
    ) {
        PlainText::assertValid($name, 200, true);
        PlainText::assertValid($country, 100, false);
        PlainText::assertValid($region, 100, false);
        PlainText::assertValid($givenName, 200, false);
        PlainText::assertValid($familyName, 200, false);
        PlainText::assertValid($biography, 5000, false, true);
        if ($birthYear !== null && ($birthYear < 1 || $birthYear > 9999)) {
            throw new \InvalidArgumentException('Invalid birth year.');
        }
    }
}
