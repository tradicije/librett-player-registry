<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Unit;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Preflight;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PreflightTest extends TestCase
{
    #[DataProvider('unsupportedTargets')]
    public function testUnsupportedTargetFailsBeforeWordPressCalls(string $phpVersion, int $integerSize, string $wordpressVersion): void
    {
        global $wp_version;
        $previous = $wp_version ?? null;
        $wp_version = $wordpressVersion;
        try {
            $this->expectException(RegistryFailure::class);
            $this->expectExceptionMessage('unsupported_runtime');
            (new Preflight())->assertReady($phpVersion, $integerSize);
        } finally {
            $wp_version = $previous;
        }
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function unsupportedTargets(): iterable
    {
        yield 'older PHP' => ['8.4.9', 8, '7.1.3'];
        yield 'unreviewed PHP branch' => ['8.6.0', 8, '7.1.3'];
        yield '32-bit' => ['8.5.5', 4, '7.1.3'];
        yield 'older WordPress' => ['8.5.5', 8, '7.0.0'];
        yield 'unreviewed WordPress branch' => ['8.5.5', 8, '7.2.0'];
    }
}
