<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

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
        $preflight = new Preflight();
        $repository = new WordPressRegistryRepository($db);
        $create = new CreateRegistry($repository, $db, new RamseyUuidGenerator());
        add_action('init', static function () use ($pluginFile): void {
            load_plugin_textdomain('librett-player-registry', false, dirname(plugin_basename($pluginFile)) . '/languages');
        });
        register_activation_hook($pluginFile, static function (bool $networkWide = false) use ($preflight, $schema): void {
            if ($networkWide || !current_user_can('activate_plugins')) {
                wp_die(esc_html__('Activation is not authorized for this installation.', 'librett-player-registry'));
            }
            try {
                $preflight->assertReady();
                $schema->install();
            } catch (RegistryFailure) {
                wp_die(esc_html__('LibreTT activation failed. Check runtime/database requirements. No registry was created.', 'librett-player-registry'));
            }
            $administrator = get_role('administrator');
            if ($administrator !== null) {
                $administrator->add_cap('librett_registry_manage_settings');
            }
        });
        (new SetupPage($repository, $create, $schema, $preflight))->register();
        // Deactivation and uninstall preserve data. Destructive purge is not implemented.
    }
}
