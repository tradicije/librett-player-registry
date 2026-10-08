<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryRepository;

final readonly class DraftSchemaPage
{
    public function __construct(private DraftSchema $schema, private Preflight $preflight, private RegistryRepository $registry) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('Private-draft schema', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_manage_settings', 'librett-registry-schema', $this->render(...));
        });
        add_action('admin_post_librett_registry_draft_schema', $this->submit(...));
    }

    public function render(): void
    {
        $this->authorize();
        echo '<div class="wrap"><h1>' . esc_html__('Private-draft schema', 'librett-player-registry') . '</h1>';
        try {
            $this->preflight->assertReady();
            $this->schema->assertComplete();
            echo '<p>' . esc_html__('Private-draft schema is ready.', 'librett-player-registry') . '</p></div>';
            return;
        } catch (RegistryFailure) {
            echo '<p>' . esc_html__('Install or resume the additive private-draft migration. Existing registry identity and records are retained.', 'librett-player-registry') . '</p>';
        }
        echo '<p>' . esc_html__('Before continuing, create and verify a backup of the database, protected media and configuration, and retain the matching plugin code. This confirmation represents your acknowledgement; the plugin does not verify or restore the backup.', 'librett-player-registry') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="librett_registry_draft_schema">';
        wp_nonce_field('librett_registry_draft_schema');
        echo '<p><label><input type="checkbox" name="backup_confirmed" value="1" required> ' . esc_html__('I have created and verified the backup.', 'librett-player-registry') . '</label></p>';
        submit_button(__('Install private-draft schema', 'librett-player-registry'));
        echo '</form></div>';
    }

    public function submit(): void
    {
        $this->authorize();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die(esc_html__('POST required.', 'librett-player-registry'), '', ['response' => 405]);
        }
        check_admin_referer('librett_registry_draft_schema');
        try {
            $this->preflight->assertReady();
            if ($this->registry->current() !== null && ($_POST['backup_confirmed'] ?? null) !== '1') {
                wp_die(esc_html__('Confirm a verified backup before changing the existing registry schema.', 'librett-player-registry'), '', ['response' => 400]);
            }
            $this->schema->install();
        } catch (RegistryFailure|InvalidArgumentException) {
            wp_die(esc_html__('The private-draft migration could not complete. Existing data is retained; resolve the runtime or schema problem before retrying.', 'librett-player-registry'), '', ['response' => 503]);
        }
        get_role('administrator')?->add_cap('librett_registry_edit_profiles');
        wp_safe_redirect(admin_url('admin.php?page=librett-registry-schema'));
        exit;
    }

    private function authorize(): void
    {
        if (!current_user_can('librett_registry_manage_settings')) {
            wp_die(esc_html__('Permission denied.', 'librett-player-registry'), '', ['response' => 403]);
        }
    }
}
