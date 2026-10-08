<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use InvalidArgumentException;

final class DraftRequest
{
    public static function text(mixed $value, int $byteLimit = 1000): string
    {
        if (!is_string($value) || strlen($value) > $byteLimit) {
            throw new InvalidArgumentException('Invalid form field.');
        }
        return wp_unslash($value);
    }

    public static function counter(mixed $value, int $maximum = PHP_INT_MAX): int
    {
        $text = self::text($value, 19);
        $bound = (string) $maximum;
        if (preg_match('/\A(?:0|[1-9][0-9]*)\z/', $text) !== 1 || strlen($text) > strlen($bound)
            || (strlen($text) === strlen($bound) && strcmp($text, $bound) > 0)) {
            throw new InvalidArgumentException('Invalid counter.');
        }
        return (int) $text;
    }
}
