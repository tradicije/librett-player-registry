<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Domain;

enum DatasetLicense: string
{
    case Odbl = 'ODbL 1.0';
    case Cc0 = 'CC0 1.0';
    case CcBy = 'CC BY 4.0';
    case CcBySa = 'CC BY-SA 4.0';
    case Reserved = 'All rights reserved';
    case Custom = 'Custom';

    public function standardTermsUrl(): ?string
    {
        return match ($this) {
            self::Odbl => 'https://opendatacommons.org/licenses/odbl/1-0/',
            self::Cc0 => 'https://creativecommons.org/publicdomain/zero/1.0/',
            self::CcBy => 'https://creativecommons.org/licenses/by/4.0/',
            self::CcBySa => 'https://creativecommons.org/licenses/by-sa/4.0/',
            self::Reserved, self::Custom => null,
        };
    }
}
