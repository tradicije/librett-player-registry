<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Application\ArchiveRelations;
use LibreTT\PlayerRegistry\Clubs\Application\SaveClubDraft;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\RelationsSchema;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubAliases;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubDraftRepository;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressMembershipRepository;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdditiveSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\InitialSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\RamseyEntityIdGenerator;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SchemaSet;
use LibreTT\PlayerRegistry\Media\Infrastructure\LocalProtectedFiles;
use LibreTT\PlayerRegistry\Media\Infrastructure\MediaSchema;
use LibreTT\PlayerRegistry\Media\Infrastructure\WordPressPhotoCatalogue;
use LibreTT\PlayerRegistry\Players\Application\SavePlayerDraft;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Players\Infrastructure\WordPressPlayerDraftRepository;
use LibreTT\PlayerRegistry\Publication\Application\ApproveProfile;
use LibreTT\PlayerRegistry\Publication\Application\ConfigurePolicy;
use LibreTT\PlayerRegistry\Publication\Application\ExportSnapshot;
use LibreTT\PlayerRegistry\Publication\Domain\DatasetLicense;
use LibreTT\PlayerRegistry\Publication\Domain\Policy;
use LibreTT\PlayerRegistry\Publication\Infrastructure\Json\SnapshotDecoder;
use LibreTT\PlayerRegistry\Publication\Infrastructure\PublicationSchema;
use LibreTT\PlayerRegistry\Publication\Infrastructure\SystemClock;
use LibreTT\PlayerRegistry\Publication\Infrastructure\WordPressPublicationStore;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\WordPressRegistryRepository;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use PHPUnit\Framework\TestCase;
use wpdb;

abstract class CatalogueTestCase extends TestCase
{
    protected Database $db;
    protected SchemaSet $schema;
    protected WordPressRegistryRepository $registry;
    protected WordPressPlayerDraftRepository $players;
    protected WordPressClubDraftRepository $clubs;
    protected WordPressMembershipRepository $memberships;
    protected WordPressClubAliases $aliases;
    protected WordPressPublicationStore $store;
    protected WordPressPhotoCatalogue $photos;
    protected LocalProtectedFiles $files;
    protected ApproveProfile $approve;
    protected ExportSnapshot $export;
    protected SnapshotDecoder $decoder;
    private string $directory;

    protected function setUp(): void
    {
        $connection = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $connection->set_prefix('ltt_catalogue_' . bin2hex(random_bytes(4)) . '_');
        $this->db = new Database($connection);
        $initial = new InitialSchema($this->db);
        $drafts = new DraftSchema($this->db, $initial);
        $relations = new AdditiveSchema($this->db, '003_club_relations', $drafts, RelationsSchema::tables());
        $publication = new AdditiveSchema($this->db, '004_publication', $relations, PublicationSchema::tables());
        $media = new AdditiveSchema($this->db, '005_protected_media', $publication, MediaSchema::tables());
        $this->schema = new SchemaSet([$initial, $drafts, $relations, $publication, $media, new AdditiveSchema($this->db, '006_snapshot_import', $media, \LibreTT\PlayerRegistry\Publication\Infrastructure\ImportSchema::tables())]);
        $this->schema->install();
        $this->registry = new WordPressRegistryRepository($this->db);
        (new CreateRegistry($this->registry, $this->db, new RamseyUuidGenerator()))->execute($this->actor(), 'Synthetic catalogue');
        $this->players = new WordPressPlayerDraftRepository($this->db);
        $this->clubs = new WordPressClubDraftRepository($this->db);
        $this->memberships = new WordPressMembershipRepository($this->db);
        $this->aliases = new WordPressClubAliases($this->db);
        $this->store = new WordPressPublicationStore($this->db);
        $this->photos = new WordPressPhotoCatalogue($this->db, 'https://example.invalid/wp-json/librett-registry/v1/media');
        $this->directory = sys_get_temp_dir() . '/ltt-photos-' . bin2hex(random_bytes(4));
        mkdir($this->directory, 0700);
        $this->files = new LocalProtectedFiles($this->directory, ABSPATH);
        $this->approve = new ApproveProfile($this->store, $this->players, $this->clubs, $this->memberships, $this->aliases, $this->registry, $this->db, $this->photos);
        $this->decoder = new SnapshotDecoder(json_decode(file_get_contents(__DIR__ . '/../../docs/contracts/schemas/public-snapshot-v1.schema.json')));
        $this->export = new ExportSnapshot($this->store, $this->registry, $this->db, new SystemClock(), $this->decoder);
    }

    protected function tearDown(): void
    {
        foreach (['import_jobs', 'import_receipts', 'import_mappings', 'import_lock', 'media_audit', 'player_photos', 'media_assets', 'publication_events', 'publication_audit', 'public_ledger', 'public_records', 'publication_head', 'publication_policy', 'memberships', 'club_aliases', 'players_audit', 'clubs_audit', 'players', 'clubs', 'identity_audit', 'identity', 'migrations'] as $table) {
            $this->db->execute('DROP TABLE IF EXISTS ' . $this->db->table($table));
        }
        foreach (glob($this->directory . '/*') as $path) {
            unlink($path);
        }
        rmdir($this->directory);
        $this->db->connection->close();
        wp_set_current_user(0);
    }

    protected function actor(): Actor
    {
        return new Actor(1, ['librett_registry_manage_settings', 'librett_registry_edit_profiles', 'librett_registry_publish_profiles', 'librett_registry_import']);
    }

    protected function configurePolicy(string $version = 'synthetic-1', int $expected = 0): void
    {
        (new ConfigurePolicy($this->store, $this->registry, $this->db))->execute($this->actor(), new Policy(
            $version,
            'Synthetic tests only',
            DatasetLicense::Cc0,
            new HttpsUrl('https://example.invalid/dataset-terms'),
            new HttpsUrl('https://example.invalid/media-terms'),
        ), $expected);
    }

    protected function savePlayer(): SavePlayerDraft
    {
        return new SavePlayerDraft($this->players, $this->registry, $this->db, new RamseyEntityIdGenerator(), new ArchiveRelations($this->memberships, $this->players, $this->store));
    }

    protected function saveClub(): SaveClubDraft
    {
        return new SaveClubDraft($this->clubs, $this->registry, $this->db, new RamseyEntityIdGenerator(), new ArchiveRelations($this->memberships, $this->players, $this->store));
    }
}
