<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryRepository;

final readonly class SetupPage
{
    public function __construct(
        private RegistryRepository $repository,
        private CreateRegistry $create,
        private InitialSchema $schema,
        private Preflight $preflight,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $capability = current_user_can('librett_registry_edit_profiles') ? 'librett_registry_edit_profiles' : 'librett_registry_manage_settings';
            add_menu_page('LibreTT Player Registry', 'LibreTT Registry', $capability, 'librett-registry', $this->render(...), 'dashicons-id');
        });
        add_action('admin_post_librett_registry_setup', $this->submit(...));
    }

    public function render(): void
    {
        if (!current_user_can('librett_registry_manage_settings') && current_user_can('librett_registry_edit_profiles')) {
            echo '<div class="wrap"><h1>LibreTT Player Registry</h1><p>' . esc_html__('Use Players or Clubs to manage private drafts.', 'librett-player-registry') . '</p></div>';
            return;
        }
        $this->assertPermission();
        echo '<div class="wrap"><h1>LibreTT Player Registry</h1>';
        try {
            $this->preflight->assertReady();
            $this->schema->assertComplete();
            $registry = $this->repository->current();
            if ($registry !== null) {
                echo '<p>' . esc_html__('Primary registry configured.', 'librett-player-registry') . '</p><p>' . esc_html($registry->name) . '</p><code>' . esc_html($registry->id->value) . '</code>';
                echo '<p>' . esc_html__('Player profiles, publication and imports are still under development.', 'librett-player-registry') . '</p></div>';
                return;
            }
            echo '<p>' . esc_html__('Create a new empty independent registry. No players or clubs will be added.', 'librett-player-registry') . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="librett_registry_setup">';
            wp_nonce_field('librett_registry_setup');
            echo '<p><label for="librett-name">' . esc_html__('Registry name', 'librett-player-registry') . '</label></p><input class="regular-text" id="librett-name" name="registry_name" maxlength="200" required>';
            submit_button(__('Create registry', 'librett-player-registry'));
            echo '</form>';
        } catch (RegistryFailure) {
            echo '<p>' . esc_html__('Setup is unavailable. Check the runtime and reactivate the plugin to resume its initial migration.', 'librett-player-registry') . '</p>';
        } catch (InvalidArgumentException) {
            echo '<p>' . esc_html__('Stored registry identity is invalid. Contact the administrator.', 'librett-player-registry') . '</p>';
        }
        echo '</div>';
    }

    public function submit(): void
    {
        $this->assertPermission();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die(esc_html__('POST required.', 'librett-player-registry'), '', ['response' => 405]);
        }
        check_admin_referer('librett_registry_setup');
        $name = $_POST['registry_name'] ?? null;
        if (!is_string($name) || strlen($name) > 1000) {
            wp_die(esc_html__('Invalid registry name.', 'librett-player-registry'), '', ['response' => 400]);
        }
        try {
            $this->preflight->assertReady();
            $this->schema->assertComplete();
            $actor = new Actor(get_current_user_id(), ['librett_registry_manage_settings']);
            $this->create->execute($actor, wp_unslash($name));
        } catch (InvalidArgumentException) {
            wp_die(esc_html__('Invalid registry name.', 'librett-player-registry'), '', ['response' => 400]);
        } catch (RegistryFailure $failure) {
            $status = $failure->errorCode === 'identity_conflict' ? 409 : 503;
            wp_die(esc_html__('Registry setup could not be completed. Existing data was preserved.', 'librett-player-registry'), '', ['response' => $status]);
        }
        wp_safe_redirect(admin_url('admin.php?page=librett-registry'));
        exit;
    }

    private function assertPermission(): void
    {
        if (!current_user_can('librett_registry_manage_settings')) {
            wp_die(esc_html__('Permission denied.', 'librett-player-registry'), '', ['response' => 403]);
        }
    }
}
