<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Unit;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftRepository;
use LibreTT\PlayerRegistry\Players\Application\SavePlayerDraft;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Application\EntityIdGenerator;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrivateDraftTest extends TestCase
{
    public function testDeniedMutationNeverGeneratesIdentityOrStartsTransaction(): void
    {
        $repo = $this->createMock(PlayerDraftRepository::class);
        $repo->expects(self::never())->method('save');
        $context = $this->createMock(RegistryContextReader::class);
        $context->expects(self::never())->method('current');
        $tx = $this->createMock(UnitOfWork::class);
        $tx->expects(self::never())->method('run');
        $ids = $this->createMock(EntityIdGenerator::class);
        $ids->expects(self::never())->method('generate');
        $this->expectException(RegistryFailure::class);
        $this->expectExceptionMessage('permission_denied');
        (new SavePlayerDraft($repo, $context, $tx, $ids))->execute(new Actor(1, []), null, new EditRevision(0), new PlayerData('Player'), DraftState::Active);
    }

    #[DataProvider('invalidFields')]
    public function testInvalidProfileFieldsAreRejected(string $field, mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlayerData(name: $field === 'name' ? $value : 'Player', biography: $field === 'biography' ? $value : '', birthYear: $field === 'birthYear' ? $value : null);
    }

    public static function invalidFields(): iterable
    {
        yield 'blank' => ['name', '  '];
        yield 'unicode length' => ['name', str_repeat('ћ', 201)];
        yield 'invalid encoding' => ['name', "\xff"];
        yield 'control' => ['name', "Player\0"];
        yield 'biography controls' => ['biography', "text\t"];
        yield 'zero year' => ['birthYear', 0];
        yield 'future format overflow' => ['birthYear', 10000];
    }

    public function testOptionalYearAndMultilineBiographyArePreserved(): void
    {
        $data = new PlayerData(name: str_repeat('ћ', 200), biography: "Line one\nLine two");
        self::assertNull($data->birthYear);
        self::assertSame("Line one\nLine two", $data->biography);
        self::assertSame(200, mb_strlen($data->name));
    }

    public function testClubAbbreviationBoundIsIndependentOfNameLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClubData(name: 'Club', abbreviation: str_repeat('C', 33));
    }

    public function testRevisionCannotWrap(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EditRevision(PHP_INT_MAX))->next();
    }
}
