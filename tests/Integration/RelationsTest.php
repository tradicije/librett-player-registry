<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Clubs\Application\SaveClubDraft;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubDraftRepository;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\InitialSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\RamseyEntityIdGenerator;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\Players\Application\SavePlayerDraft;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\Players\Infrastructure\WordPressPlayerDraftRepository;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\WordPressRegistryRepository;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdditiveSchema;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\RelationsSchema;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressMembershipRepository;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubAliases;
use LibreTT\PlayerRegistry\Clubs\Application\SetMemberships;
use LibreTT\PlayerRegistry\Clubs\Application\SetClubAliases;
use LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision;
use LibreTT\PlayerRegistry\Application\ChangeMemberships;
use LibreTT\PlayerRegistry\Application\ArchiveRelations;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use PHPUnit\Framework\TestCase;
use wpdb;

final class RelationsTest extends TestCase
{
    private Database $db;
    private InitialSchema $initial;
    private DraftSchema $schema;
    private WordPressRegistryRepository $registry;
    private WordPressPlayerDraftRepository $players;
    private SavePlayerDraft $save;
    private AdditiveSchema $relations;
    private WordPressMembershipRepository $memberships;
    private WordPressClubDraftRepository $clubs;
    private ChangeMemberships $change;

    protected function setUp(): void
    {
        $connection = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $connection->set_prefix('ltt_drafts_' . bin2hex(random_bytes(4)) . '_');
        $this->db = new Database($connection);
        $this->initial = new InitialSchema($this->db);
        $this->initial->install();
        $this->schema = new DraftSchema($this->db, $this->initial);
        $this->registry = new WordPressRegistryRepository($this->db);
        $this->players = new WordPressPlayerDraftRepository($this->db);
        $this->relations = new AdditiveSchema($this->db, '003_club_relations', $this->schema, RelationsSchema::tables());
        $this->memberships = new WordPressMembershipRepository($this->db);
        $this->clubs = new WordPressClubDraftRepository($this->db);
        $this->change = new ChangeMemberships($this->db, new TouchPlayerDraftRevision($this->players), new SetMemberships($this->memberships, $this->players, $this->registry));
        $this->save = new SavePlayerDraft($this->players, $this->registry, $this->db, new RamseyEntityIdGenerator());
    }

    protected function tearDown(): void
    {
        foreach (['memberships', 'club_aliases', 'players_audit', 'clubs_audit', 'players', 'clubs', 'identity_audit', 'identity', 'migrations'] as $table) {
            $this->db->execute('DROP TABLE IF EXISTS ' . $this->db->table($table));
        }
        $this->db->connection->close();
    }

    private function actor(): Actor
    {
        return new Actor(1, ['librett_registry_manage_settings', 'librett_registry_edit_profiles']);
    }

    private function configure(): void
    {
        (new CreateRegistry($this->registry, $this->db, new RamseyUuidGenerator()))->execute($this->actor(), 'Synthetic registry');
        $this->schema->install();
        $this->relations->install();
    }

    private function create(string $name = 'Synthetic player'): PlayerDraft
    {
        return $this->save->execute($this->actor(), null, new EditRevision(0), new PlayerData($name), DraftState::Active);
    }

    public function testMembershipRejectsMissingClubAndRollsBackPlayerRevision(): void
    {
        $this->configure();
        $player = $this->create();
        try {
            $this->change->execute($this->actor(), $player->id, $player->revision, [(new RamseyEntityIdGenerator())->generate()]);
            self::fail('Missing club accepted.');
        } catch (RegistryFailure $failure) {
            self::assertSame('invalid_data', $failure->errorCode);
        }
        self::assertSame(1, $this->players->find($player->id)->revision->value);
        self::assertSame([], $this->memberships->forPlayer($player->id));
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('players_audit')));
    }

    public function testMembershipStaleRevisionAndArchiveAreAtomic(): void
    {
        $this->configure();
        $player = $this->create();
        $archive = new ArchiveRelations($this->memberships, $this->players);
        $saveClub = new SaveClubDraft($this->clubs, $this->registry, $this->db, new RamseyEntityIdGenerator(), $archive);
        $club = $saveClub->execute($this->actor(), null, new EditRevision(0), new ClubData('Synthetic club'), DraftState::Active);
        $this->change->execute($this->actor(), $player->id, $player->revision, [$club->id]);
        self::assertSame([$club->id->value], array_map(static fn(EntityId $id): string => $id->value, $this->memberships->forPlayer($player->id)));
        try {
            $this->change->execute($this->actor(), $player->id, $player->revision, []);
            self::fail('Stale revision accepted.');
        } catch (RegistryFailure $failure) {
            self::assertSame('revision_conflict', $failure->errorCode);
        }
        $saveClub->execute($this->actor(), $club->id, $club->revision, $club->data, DraftState::Archived);
        self::assertSame([], $this->memberships->forPlayer($player->id));
        self::assertSame(3, $this->players->find($player->id)->revision->value);
    }

    public function testAliasesRetainIdentityAndRejectDuplicatesWithoutMutation(): void
    {
        $this->configure();
        $club = (new SaveClubDraft($this->clubs, $this->registry, $this->db, new RamseyEntityIdGenerator()))->execute($this->actor(), null, new EditRevision(0), new ClubData('Synthetic club'), DraftState::Active);
        $aliases = new WordPressClubAliases($this->db);
        $set = new SetClubAliases($this->clubs, $aliases, $this->registry, $this->db);
        $set->execute($this->actor(), $club->id, $club->revision, ['Synthetic alias']);
        self::assertSame(['Synthetic alias'], $aliases->forClub($club->id));
        try {
            $set->execute($this->actor(), $club->id, new EditRevision(2), ['same', 'same']);
            self::fail('Duplicate aliases accepted.');
        } catch (RegistryFailure $failure) {
            self::assertSame('invalid_data', $failure->errorCode);
        }
        self::assertSame(2, $this->clubs->find($club->id)->revision->value);
        $this->relations->install();
        $this->schema->assertComplete();
        $this->initial->assertComplete();
        self::assertSame($club->id->value, $this->clubs->find($club->id)->id->value);
    }
}
