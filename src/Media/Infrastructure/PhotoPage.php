<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Application\ChangePhoto;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdminGuard;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\UploadRequest;
use LibreTT\PlayerRegistry\Media\Application\PhotoCatalogue;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class PhotoPage
{
    public function __construct(private ChangePhoto $change, private PhotoCatalogue $photos, private PlayerDraftReader $players, private AdminGuard $guard) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('Player photographs', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_edit_profiles', 'librett-registry-photos', $this->render(...));
        });
        add_action('admin_post_librett_registry_photo', $this->submit(...));
    }

    public function render(): void
    {
        $actor = $this->guard->actor('librett_registry_edit_profiles');
        $query = DraftRequest::text($_GET['q'] ?? '', 800);
        echo '<div class="wrap"><h1>' . esc_html__('Player photographs', 'librett-player-registry') . '</h1><form method="get"><input type="hidden" name="page" value="librett-registry-photos"><input name="q" value="' . esc_attr($query) . '">';
        submit_button(__('Search players', 'librett-player-registry'), 'secondary', '', false);
        echo '</form><p>' . esc_html__('JPEG or PNG, maximum 5 MiB and 4096 pixels per side. Uploads are kept outside public storage; a photo becomes public only after profile approval.', 'librett-player-registry') . '</p>';
        foreach ($this->players->search($actor, $query) as $player) {
            echo '<h2>' . esc_html($player->data->name) . '</h2><p>' . esc_html($player->id->value) . '</p>';
            $photo = $this->photos->forPlayer($player->id);
            if ($photo !== null) {
                echo '<p>' . esc_html($photo->attribution . ' · ' . $photo->width . ' × ' . $photo->height) . '</p><img alt="" style="max-width:200px;max-height:200px" src="' . esc_url(add_query_arg('_wpnonce', wp_create_nonce('wp_rest'), rest_url('librett-registry/v1/private-media/' . $photo->id->value))) . '">';
            }
            AdminGuard::form('librett_registry_photo', true);
            AdminGuard::hidden('id', $player->id->value);
            AdminGuard::hidden('revision', (string) $player->revision->value);
            echo '<p><input type="file" name="photo" accept="image/jpeg,image/png"></p>';
            AdminGuard::field('attribution', __('Public photographer/rights attribution', 'librett-player-registry'), '', 500);
            AdminGuard::field('rights', __('Private photo publication authorization/reference', 'librett-player-registry'));
            echo '<button class="button button-primary" name="decision" value="upload">' . esc_html__('Save private photo', 'librett-player-registry') . '</button> <button class="button" name="decision" value="remove">' . esc_html__('Remove draft photo', 'librett-player-registry') . '</button></form>';
        }
        echo '</div>';
    }

    public function submit(): void
    {
        try {
            $actor = $this->guard->actor('librett_registry_edit_profiles');
            $this->guard->post('librett_registry_photo');
            $decision = DraftRequest::text($_POST['decision'] ?? null, 16);
            if (!in_array($decision, ['upload','remove'], true)) {
                throw new InvalidArgumentException('Unknown photo decision.');
            }
            $bytes = $decision === 'remove' ? null : UploadRequest::bytes('photo', 5242880);
            if ($decision === 'upload' && $bytes === null) {
                throw new InvalidArgumentException('Photo required.');
            }
            $this->change->execute($actor, new EntityId(DraftRequest::text($_POST['id'] ?? null, 36)), new EditRevision(DraftRequest::counter($_POST['revision'] ?? null)), $bytes, DraftRequest::text($_POST['attribution'] ?? '', 2000), DraftRequest::text($_POST['rights'] ?? '', 4000));
        } catch (RegistryFailure|InvalidArgumentException) {
            wp_die(esc_html__('Photo was not changed. Check permissions, revision, protected storage and image limits.', 'librett-player-registry'), '', ['response' => 400]);
        }
        wp_safe_redirect(admin_url('admin.php?page=librett-registry-photos'));
        exit;
    }
}
