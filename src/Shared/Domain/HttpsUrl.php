<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Shared\Domain;

final readonly class HttpsUrl
{
    public function __construct(public string $value)
    {
        $parts = parse_url($value);
        if (strlen($value) > 2048 || preg_match('/[\x00-\x20\x7f]/', $value) === 1 || !is_array($parts)
            || ($parts['scheme'] ?? '') !== 'https' || !isset($parts['host']) || $parts['host'] === ''
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || isset($parts['port']) && $parts['port'] < 1) {
            throw new \InvalidArgumentException('An absolute HTTPS URL without credentials or fragment is required.');
        }
    }
}
