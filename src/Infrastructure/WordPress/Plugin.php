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
        $preflight = new Preflight();
        $repository = new WordPressRegistryRepository($db);
        $create = new CreateRegistry($repository, $db, new RamseyUuidGenerator());
        add_action('init', static function () use ($pluginFile): void {
            load_plugin_textdomain('librett-player-registry', false, dirname(plugin_basename($pluginFile)) . '/languages');
        });
        register_activation_hook($pluginFile, static function (bool $networkWide = false) use ($preflight, $schema, $draftSchema, $repository): void {
            if ($networkWide || !current_user_can('activate_plugins')) {
                wp_die(esc_html__('Activation is not authorized for this installation.', 'librett-player-registry'));
            }
            try {
                $preflight->assertReady();
                $schema->install();
                if ($repository->current() === null) {
                    $draftSchema->install();
                }
            } catch (RegistryFailure|InvalidArgumentException) {
                wp_die(esc_html__('LibreTT activation failed. Check runtime/database requirements. No registry was created.', 'librett-player-registry'));
            }
            $administrator = get_role('administrator');
            if ($administrator !== null) {
                $administrator->add_cap('librett_registry_manage_settings');
                $administrator->add_cap('librett_registry_edit_profiles');
            }
        });
        (new SetupPage($repository, $create, $schema, $preflight))->register();
        (new DraftSchemaPage($draftSchema, $preflight, $repository))->register();
        add_action('init', static function () use ($db, $repository, $draftSchema, $preflight): void {
            $ids = new RamseyEntityIdGenerator();
            $players = new WordPressPlayerDraftRepository($db);
            $clubs = new WordPressClubDraftRepository($db);
            PlayerPage::register(
                new PlayerDraftReader($players, $repository),
                new SavePlayerDraft($players, $repository, $db, $ids),
                $draftSchema,
                $preflight,
            );
            ClubPage::register(
                new ClubDraftReader($clubs, $repository),
                new SaveClubDraft($clubs, $repository, $db, $ids),
                $draftSchema,
                $preflight,
            );
        }, 20);
        // Deactivation and uninstall preserve data. Destructive purge is not implemented.
    }
}
