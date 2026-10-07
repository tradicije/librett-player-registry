<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Unit;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryRepository;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Domain\Registry;
use LibreTT\PlayerRegistry\RegistryIdentity\Domain\RegistryId;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegistryIdentityTest extends TestCase
{
    private const string ID = '11111111-1111-4111-8111-111111111111';

    public function testUnauthorizedActorDoesNotGenerateIdOrWrite(): void
    {
        $repo = $this->createMock(RegistryRepository::class);
        $repo->expects(self::never())->method('createIfUnconfigured');
        $ids = $this->createMock(UuidGenerator::class);
        $ids->expects(self::never())->method('generate');
        $tx = $this->createMock(UnitOfWork::class);
        $tx->expects(self::never())->method('run');
        $this->expectException(RegistryFailure::class);
        $this->expectExceptionMessage('permission_denied');
        (new CreateRegistry($repo, $tx, $ids))->execute(new Actor(3, []), 'Registry');
    }

    public function testAuthorizedSetupRunsOneTransactionWithActorAndFreshIdentity(): void
    {
        $repo = $this->createMock(RegistryRepository::class);
        $repo->expects(self::once())->method('createIfUnconfigured')->with(
            self::callback(static fn(Registry $registry): bool => $registry->id->value === self::ID && $registry->name === 'Ћирилични регистар'),
            7,
        );
        $ids = $this->createStub(UuidGenerator::class);
        $ids->method('generate')->willReturn(new RegistryId(self::ID));
        $tx = $this->createMock(UnitOfWork::class);
        $tx->expects(self::once())->method('run')->willReturnCallback(static function (callable $operation): void {
            $operation();
        });
        $result = (new CreateRegistry($repo, $tx, $ids))->execute(new Actor(7, ['librett_registry_manage_settings']), 'Ћирилични регистар');
        self::assertSame(self::ID, $result->id->value);
    }

    #[DataProvider('invalidNames')]
    public function testInvalidNameNeverStartsTransaction(string $name): void
    {
        $repo = $this->createMock(RegistryRepository::class);
        $repo->expects(self::never())->method('createIfUnconfigured');
        $tx = $this->createMock(UnitOfWork::class);
        $tx->expects(self::never())->method('run');
        $ids = $this->createStub(UuidGenerator::class);
        $ids->method('generate')->willReturn(new RegistryId(self::ID));
        $this->expectException(InvalidArgumentException::class);
        (new CreateRegistry($repo, $tx, $ids))->execute(new Actor(1, ['librett_registry_manage_settings']), $name);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
        yield 'newline' => ["name\n"];
        yield 'control' => ["name\0"];
        yield 'invalid encoding' => ["\xff"];
        yield 'too long Unicode' => [str_repeat('ћ', 201)];
    }

    public function testUnicodeLimitCountsCharactersAndPreservesText(): void
    {
        $name = str_repeat('ћ', 200);
        self::assertSame($name, (new Registry(new RegistryId(self::ID), $name))->name);
    }

    public function testNilIdentityIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RegistryId('00000000-0000-0000-0000-000000000000');
    }

    public function testProductionGeneratorCreatesIndependentCanonicalRandomIdentities(): void
    {
        $generator = new RamseyUuidGenerator();
        $a = $generator->generate();
        $b = $generator->generate();
        self::assertNotSame($a->value, $b->value);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $a->value);
    }
}
