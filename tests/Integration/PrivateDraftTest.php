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
use PHPUnit\Framework\TestCase;
use wpdb;

final class PrivateDraftTest extends TestCase
{
    private Database $db;
    private InitialSchema $initial;
    private DraftSchema $schema;
    private WordPressRegistryRepository $registry;
    private WordPressPlayerDraftRepository $players;
    private SavePlayerDraft $save;

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
        $this->save = new SavePlayerDraft($this->players, $this->registry, $this->db, new RamseyEntityIdGenerator());
    }

    protected function tearDown(): void
    {
        foreach (['players_audit', 'clubs_audit', 'players', 'clubs', 'identity_audit', 'identity', 'migrations'] as $table) {
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
    }

    private function create(string $name = 'Synthetic player'): PlayerDraft
    {
        return $this->save->execute($this->actor(), null, new EditRevision(0), new PlayerData($name), DraftState::Active);
    }

    public function testMigrationIsEmptyReentrantAndPreservesConfiguredIdentity(): void
    {
        $this->configure();
        $id = $this->registry->current()->id->value;
        $this->schema->install();
        $this->initial->install();
        self::assertSame($id, $this->registry->current()->id->value);
        self::assertSame([], $this->players->search('', 0));
        self::assertCount(7, $this->db->rows($this->db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE %s', $this->db->connection->esc_like($this->db->connection->prefix . 'librett_registry_') . '%')));
    }

    public function testSameNamesStayIndependentAndStaleWritesCannotReplaceDraft(): void
    {
        $this->configure();
        $a = $this->create();
        $b = $this->create();
        self::assertNotSame($a->id->value, $b->id->value);
        self::assertCount(2, $this->players->search('Synthetic', 0));
        $updated = $this->save->execute($this->actor(), $a->id, $a->revision, new PlayerData(name: 'Changed', birthYear: 2000), DraftState::Active);
        self::assertSame(2, $updated->revision->value);
        try {
            $this->save->execute($this->actor(), $a->id, $a->revision, new PlayerData('Stale'), DraftState::Active);
            self::fail('Expected stale edit rejection');
        } catch (RegistryFailure $e) {
            self::assertSame('revision_conflict', $e->errorCode);
        }
        self::assertSame('Changed', $this->players->find($a->id)->data->name);
        self::assertCount(3, $this->db->rows('SELECT * FROM ' . $this->db->table('players_audit')));
    }

    public function testArchiveRestoreAndMissingYearRetainIdentity(): void
    {
        $this->configure();
        $a = $this->create();
        self::assertNull($this->players->find($a->id)->data->birthYear);
        $archived = $this->save->execute($this->actor(), $a->id, $a->revision, $a->data, DraftState::Archived);
        $restored = $this->save->execute($this->actor(), $a->id, $archived->revision, $a->data, DraftState::Active);
        self::assertSame($a->id->value, $restored->id->value);
        self::assertSame(DraftState::Active, $this->players->find($a->id)->state);
        self::assertSame(['created', 'archived', 'restored'], array_column($this->db->rows('SELECT action FROM ' . $this->db->table('players_audit') . ' ORDER BY id'), 'action'));
    }

    public function testAuditFailureRollsBackDraftUpdate(): void
    {
        $this->configure();
        $a = $this->create();
        $this->db->execute('DROP TABLE ' . $this->db->table('players_audit'));
        try {
            $this->save->execute($this->actor(), $a->id, $a->revision, new PlayerData('Should roll back'), DraftState::Active);
            self::fail('Expected failed audit');
        } catch (RegistryFailure $e) {
            self::assertSame('storage_failure', $e->errorCode);
        }
        self::assertSame($a->data->name, $this->players->find($a->id)->data->name);
        self::assertSame(1, $this->players->find($a->id)->revision->value);
    }

    public function testUnconfiguredRegistryAndUnauthorizedReadsAreRejected(): void
    {
        $this->schema->install();
        try {
            $this->create();
            self::fail('Expected setup requirement');
        } catch (RegistryFailure $e) {
            self::assertSame('registry_unconfigured', $e->errorCode);
        }
        self::assertSame([], $this->players->search('', 0));
        $this->expectExceptionMessage('permission_denied');
        (new PlayerDraftReader($this->players, $this->registry))->search(new Actor(1, []), '');
    }

    public function testMigrationResumesPartialCreationAndRejectsChecksumMismatch(): void
    {
        $this->schema->install();
        $this->db->execute('DROP TABLE ' . $this->db->table('clubs_audit'));
        $this->db->execute("UPDATE " . $this->db->table('migrations') . " SET state = 'started' WHERE migration_id = '002_private_drafts'");
        $this->schema->install();
        $this->schema->assertComplete();
        $this->db->execute("UPDATE " . $this->db->table('migrations') . " SET checksum = REPEAT('0',64) WHERE migration_id = '002_private_drafts'");
        $this->expectExceptionMessage('migration_checksum_mismatch');
        $this->schema->install();
    }

    public function testNonTransactionalDraftStorageIsRejected(): void
    {
        $this->schema->install();
        $this->db->execute('ALTER TABLE ' . $this->db->table('players') . ' ENGINE=MyISAM');
        $this->expectExceptionMessage('schema_mismatch');
        $this->schema->assertComplete();
    }

    public function testClubRepositoryUsesItsOwnDraftAndAuditTables(): void
    {
        $this->configure();
        $repo = new WordPressClubDraftRepository($this->db);
        $club = (new SaveClubDraft($repo, $this->registry, $this->db, new RamseyEntityIdGenerator()))->execute($this->actor(), null, new EditRevision(0), new ClubData(name: 'Synthetic club', abbreviation: 'SC'), DraftState::Active);
        self::assertSame('SC', $repo->find($club->id)->data->abbreviation);
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('clubs_audit')));
        self::assertSame([], $this->players->search('', 0));
    }
}
