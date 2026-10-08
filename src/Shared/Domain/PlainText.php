<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Domain;

use InvalidArgumentException;

final class PlainText
{
    public static function assertValid(string $value, int $limit, bool $required = false, bool $multiline = false): void
    {
        $controls = $multiline ? '/[\x00-\x09\x0b-\x1f\x7f-\x9f]/u' : '/[\x00-\x1f\x7f-\x9f]/u';
        if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') > $limit
            || preg_match($controls, $value) === 1
            || (($required || $value !== '') && preg_match('/\S/u', $value) !== 1)) {
            throw new InvalidArgumentException('Invalid plain text.');
        }
    }
}
