<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Unit;

use LibreTT\PlayerRegistry\Publication\Infrastructure\Json\SnapshotDecoder;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SnapshotDecoderTest extends TestCase
{
    private function decoder(): SnapshotDecoder
    {
        return new SnapshotDecoder(json_decode(file_get_contents(__DIR__ . '/../../docs/contracts/schemas/public-snapshot-v1.schema.json'), false, 64, JSON_THROW_ON_ERROR));
    }

    /** @return iterable<string,array{string,bool}> */
    public static function fixtures(): iterable
    {
        foreach (glob(__DIR__ . '/../../docs/contracts/examples/*.json') as $path) {
            yield basename($path) => [$path, !str_starts_with(basename($path), 'reject-')];
        }
    }

    #[DataProvider('fixtures')]
    public function testReviewedContractExamples(string $path, bool $accepted): void
    {
        if (!$accepted) {
            $this->expectException(RegistryFailure::class);
        }
        $snapshot = $this->decoder()->decode(file_get_contents($path));
        self::assertSame('librett-registry-public-snapshot', $snapshot->format);
    }

    /** @return iterable<string,array{string}> */
    public static function malformed(): iterable
    {
        yield 'trailing content' => ['{} {}'];
        yield 'unclosed' => ['{"players": ['];
        yield 'decoded duplicate key' => ['{"name":1,"na\\u006de":2}'];
        yield 'depth' => ['[[[[[[[[[]]]]]]]]]'];
        yield 'oversized token' => ['{"x":"' . str_repeat('a', 32769) . '"}'];
        yield 'UTF8' => ["{\"x\":\"\xff\"}"];
        yield 'lone surrogate' => ['{"x":"\\ud800"}'];
        yield 'non finite' => ['{"x":1e999}'];
    }

    #[DataProvider('malformed')]
    public function testMalformedAndResourceLimits(string $json): void
    {
        $this->expectException(RegistryFailure::class);
        $this->decoder()->decode($json);
    }

    public function testInvalidCalendarAndURLRejected(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../../docs/contracts/examples/empty.json'));
        $data->exported_at = '2026-02-30T00:00:00Z';
        $this->expectException(RegistryFailure::class);
        $this->decoder()->decode(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
