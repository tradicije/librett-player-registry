<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftReader;
use LibreTT\PlayerRegistry\Clubs\Application\SaveClubDraft;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\ClubPage;
use LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubDraftRepository;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\Players\Application\SavePlayerDraft;
use LibreTT\PlayerRegistry\Players\Infrastructure\PlayerPage;
use LibreTT\PlayerRegistry\Players\Infrastructure\WordPressPlayerDraftRepository;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\WordPressRegistryRepository;

final class Plugin
{
    public static function register(string $pluginFile): void
    {
        global $wpdb;
        if (!$wpdb instanceof \wpdb) {
            throw new RegistryFailure('storage_unavailable');
        }
        $db = new Database($wpdb);
        $schema = new InitialSchema($db);
        $draftSchema = new DraftSchema($db, $schema);
        $relationsSchema = new AdditiveSchema($db, '003_club_relations', $draftSchema, \LibreTT\PlayerRegistry\Clubs\Infrastructure\RelationsSchema::tables());
        $publicationSchema = new AdditiveSchema($db, '004_publication', $relationsSchema, \LibreTT\PlayerRegistry\Publication\Infrastructure\PublicationSchema::tables());
        $mediaSchema = new AdditiveSchema($db, '005_protected_media', $publicationSchema, \LibreTT\PlayerRegistry\Media\Infrastructure\MediaSchema::tables());
        $importSchema = new AdditiveSchema($db, '006_snapshot_import', $mediaSchema, \LibreTT\PlayerRegistry\Publication\Infrastructure\ImportSchema::tables());
        $catalogueSchema = new SchemaSet([$schema, $draftSchema, $relationsSchema, $publicationSchema, $mediaSchema, $importSchema]);
        $preflight = new Preflight();
        $repository = new WordPressRegistryRepository($db);
        $create = new CreateRegistry($repository, $db, new RamseyUuidGenerator());
        add_action('init', static function () use ($pluginFile): void {
            load_plugin_textdomain('librett-player-registry', false, dirname(plugin_basename($pluginFile)) . '/languages');
        });
        register_activation_hook($pluginFile, static function (bool $networkWide = false) use ($preflight, $schema, $catalogueSchema, $repository): void {
            if ($networkWide || !current_user_can('activate_plugins')) {
                wp_die(esc_html__('Activation is not authorized for this installation.', 'librett-player-registry'));
            }
            try {
                $preflight->assertReady();
                $schema->install();
                if ($repository->current() === null) {
                    $catalogueSchema->install();
                }
            } catch (RegistryFailure|InvalidArgumentException) {
                wp_die(esc_html__('LibreTT activation failed. Check runtime/database requirements. No registry was created.', 'librett-player-registry'));
            }
            $administrator = get_role('administrator');
            if ($administrator !== null) {
                $administrator->add_cap('librett_registry_manage_settings');
                $administrator->add_cap('librett_registry_edit_profiles');
                $administrator->add_cap('librett_registry_publish_profiles');
                $administrator->add_cap('librett_registry_import');
            }
        });
        (new SetupPage($repository, $create, $schema, $preflight))->register();
        (new DraftSchemaPage($catalogueSchema, $preflight, $repository))->register();
        add_action('init', static function () use ($db, $repository, $relationsSchema, $catalogueSchema, $preflight): void {
            $ids = new RamseyEntityIdGenerator();
            $players = new WordPressPlayerDraftRepository($db);
            $clubs = new WordPressClubDraftRepository($db);
            $memberships = new \LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressMembershipRepository($db);
            $aliases = new \LibreTT\PlayerRegistry\Clubs\Infrastructure\WordPressClubAliases($db);
            $publications = new \LibreTT\PlayerRegistry\Publication\Infrastructure\WordPressPublicationStore($db);
            $archive = new \LibreTT\PlayerRegistry\Application\ArchiveRelations($memberships, $players, $publications);
            $privateDirectory = defined('LIBRETT_PRIVATE_STORAGE') ? constant('LIBRETT_PRIVATE_STORAGE') : (getenv('LIBRETT_PRIVATE_STORAGE') ?: '');
            $wordpressRoot = defined('ABSPATH') ? constant('ABSPATH') : null;
            $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? $wordpressRoot;
            if ($documentRoot === '') {
                $documentRoot = $wordpressRoot;
            }
            if (!is_string($privateDirectory) || !is_string($documentRoot) || !is_string($wordpressRoot)) {
                throw new RegistryFailure('protected_storage_unavailable');
            }
            $files = new \LibreTT\PlayerRegistry\Media\Infrastructure\LocalProtectedFiles($privateDirectory, $documentRoot, $wordpressRoot);
            $photos = new \LibreTT\PlayerRegistry\Media\Infrastructure\WordPressPhotoCatalogue($db, rest_url('librett-registry/v1/media'));
            $guard = new AdminGuard($catalogueSchema, $preflight);
            (new \LibreTT\PlayerRegistry\Publication\Infrastructure\PolicyPage(
                $publications,
                new \LibreTT\PlayerRegistry\Publication\Application\ConfigurePolicy($publications, $repository, $db),
                new \LibreTT\PlayerRegistry\Publication\Infrastructure\LicenseDocumentUpload($files),
                $guard,
            ))->register();
            (new \LibreTT\PlayerRegistry\Publication\Infrastructure\PublicationPage(
                $publications,
                new \LibreTT\PlayerRegistry\Publication\Application\ApproveProfile($publications, $players, $clubs, $memberships, $aliases, $repository, $db, $photos),
                new \LibreTT\PlayerRegistry\Publication\Application\WithdrawProfile($publications, $db),
                new PlayerDraftReader($players, $repository),
                new ClubDraftReader($clubs, $repository),
                $guard,
            ))->register();
            (new \LibreTT\PlayerRegistry\Media\Infrastructure\PhotoPage(
                new \LibreTT\PlayerRegistry\Application\ChangePhoto(
                    new \LibreTT\PlayerRegistry\Media\Infrastructure\GdPhotoProcessor($files),
                    $photos,
                    new \LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision($players),
                    $players,
                    $repository,
                    $db,
                ),
                $photos,
                new PlayerDraftReader($players, $repository),
                $guard,
            ))->register();
            (new \LibreTT\PlayerRegistry\Media\Infrastructure\MediaDelivery($photos, $files, $publications, $catalogueSchema))->register();
            $schemaJson = file_get_contents(dirname(__DIR__, 3) . '/docs/contracts/schemas/public-snapshot-v1.schema.json');
            if ($schemaJson === false) {
                throw new RegistryFailure('snapshot_schema_unavailable');
            }
            $jsonSchema = json_decode($schemaJson, false, 64, JSON_THROW_ON_ERROR);
            if (!$jsonSchema instanceof \stdClass) {
                throw new RegistryFailure('snapshot_schema_unavailable');
            }
            $decoder = new \LibreTT\PlayerRegistry\Publication\Infrastructure\Json\SnapshotDecoder($jsonSchema);
            $imports = new \LibreTT\PlayerRegistry\Publication\Infrastructure\WordPressImportStore($db);
            $importTarget = new \LibreTT\PlayerRegistry\Application\DraftImportTarget($players, $clubs, $memberships, $aliases);
            $clock = new \LibreTT\PlayerRegistry\Publication\Infrastructure\SystemClock();
            (new \LibreTT\PlayerRegistry\Publication\Infrastructure\ImportPage(
                new \LibreTT\PlayerRegistry\Publication\Application\StageImport($decoder, $imports, $importTarget, $repository, $ids, $clock, $db),
                new \LibreTT\PlayerRegistry\Publication\Application\ConfirmImport($decoder, $imports, $importTarget, $repository, $clock, $db),
                $imports,
                $decoder,
                $guard,
            ))->register();
            $export = new \LibreTT\PlayerRegistry\Publication\Application\ExportSnapshot(
                $publications,
                $repository,
                $db,
                new \LibreTT\PlayerRegistry\Publication\Infrastructure\SystemClock(),
                $decoder,
            );
            (new \LibreTT\PlayerRegistry\Publication\Infrastructure\PublicApi($export, $catalogueSchema))->register();
            (new \LibreTT\PlayerRegistry\Publication\Infrastructure\PublicProfiles($export, $catalogueSchema))->register();
            (new RelationsPage(
                new PlayerDraftReader($players, $repository),
                new ClubDraftReader($clubs, $repository),
                $memberships,
                $aliases,
                new \LibreTT\PlayerRegistry\Application\ChangeMemberships(
                    $db,
                    new \LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision($players),
                    new \LibreTT\PlayerRegistry\Clubs\Application\SetMemberships($memberships, $players, $repository),
                ),
                new \LibreTT\PlayerRegistry\Clubs\Application\SetClubAliases($clubs, $aliases, $repository, $db),
                $relationsSchema,
                $preflight,
            ))->register();
            PlayerPage::register(
                new PlayerDraftReader($players, $repository),
                new SavePlayerDraft($players, $repository, $db, $ids, $archive),
                $catalogueSchema,
                $preflight,
            );
            ClubPage::register(
                new ClubDraftReader($clubs, $repository),
                new SaveClubDraft($clubs, $repository, $db, $ids, $archive),
                $catalogueSchema,
                $preflight,
            );
        }, 20);
        // Deactivation and uninstall preserve data. Destructive purge is not implemented.
    }
}
