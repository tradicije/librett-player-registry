<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftReader;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdminGuard;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\Publication\Application\ApproveProfile;
use LibreTT\PlayerRegistry\Publication\Application\PublicationStore;
use LibreTT\PlayerRegistry\Publication\Application\WithdrawProfile;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class PublicationPage
{
    public function __construct(
        private PublicationStore $store,
        private ApproveProfile $approve,
        private WithdrawProfile $withdraw,
        private PlayerDraftReader $players,
        private ClubDraftReader $clubs,
        private AdminGuard $guard,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('Publication review', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_publish_profiles', 'librett-registry-publication', $this->render(...));
        });
        add_action('admin_post_librett_registry_publication', $this->submit(...));
    }

    public function render(): void
    {
        $actor = $this->guard->actor('librett_registry_publish_profiles');
        // Viewing private drafts independently requires the editor capability.
        if (!current_user_can('librett_registry_edit_profiles')) {
            wp_die(esc_html__('Profile editing permission is required to inspect drafts.', 'librett-player-registry'), '', ['response' => 403]);
        }
        $reader = new Actor($actor->id, ['librett_registry_edit_profiles']);
        $policy = $this->store->policy();
        echo '<div class="wrap"><h1>' . esc_html__('Publication review', 'librett-player-registry') . '</h1>';
        if ($policy === null) {
            echo '<p>' . esc_html__('Configure the license and publication policy first.', 'librett-player-registry') . '</p></div>';
            return;
        }
        $query = DraftRequest::text($_GET['q'] ?? '', 800);
        echo '<form method="get"><input type="hidden" name="page" value="librett-registry-publication"><input name="q" value="' . esc_attr($query) . '">';
        submit_button(__('Search drafts', 'librett-player-registry'), 'secondary', '', false);
        echo '</form><p>' . esc_html($policy['policy']->purpose . ' · ' . $policy['policy']->license->value . ' · ' . $policy['policy']->version) . '</p>';
        foreach (['club' => $this->clubs->search($reader, $query), 'player' => $this->players->search($reader, $query)] as $type => $drafts) {
            foreach ($drafts as $draft) {
                echo '<hr><h2>' . esc_html($draft->data->name) . '</h2><p>' . esc_html($type . ' · ' . $draft->id->value . ' · ' . $draft->state->value) . '</p><dl>';
                foreach (get_object_vars($draft->data) as $field => $value) {
                    echo '<dt>' . esc_html($this->fieldLabel($field)) . '</dt><dd>' . esc_html(is_string($value) || is_int($value) ? (string) $value : '') . '</dd>';
                }
                echo '</dl>';
                AdminGuard::form('librett_registry_publication');
                AdminGuard::hidden('type', $type);
                AdminGuard::hidden('id', $draft->id->value);
                AdminGuard::hidden('draft_revision', (string) $draft->revision->value);
                AdminGuard::hidden('public_revision', (string) $this->store->revision($type, $draft->id));
                AdminGuard::hidden('policy_version', $policy['policy']->version);
                echo '<p>' . esc_html__('Name and identifier are required. Select each additional field you authorize for public profiles, API and download.', 'librett-player-registry') . '</p>';
                foreach ($type === 'player' ? ApproveProfile::PLAYER_FIELDS : ApproveProfile::CLUB_FIELDS as $field) {
                    echo '<label><input type="checkbox" name="fields[]" value="' . esc_attr($field) . '"> ' . esc_html($this->fieldLabel($field)) . '</label> ';
                }
                if ($type === 'player') {
                    echo '<p><label>' . esc_html__('Reviewed age status', 'librett-player-registry') . ' <select name="age_status"><option value="unknown">' . esc_html__('Unknown: publication blocked', 'librett-player-registry') . '</option><option value="adult">' . esc_html__('Adult', 'librett-player-registry') . '</option><option value="minor">' . esc_html__('Minor: requires configured policy', 'librett-player-registry') . '</option></select></label></p>';
                }
                AdminGuard::field('evidence', __('Private authorization basis/reference (not exported)', 'librett-player-registry'));
                echo '<p><button class="button button-primary" name="decision" value="approve">' . esc_html__('Approve selected publication', 'librett-player-registry') . '</button> <button class="button" name="decision" value="withdraw">' . esc_html__('Withdraw publication', 'librett-player-registry') . '</button></p></form>';
            }
        }
        echo '</div>';
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => __('Display name', 'librett-player-registry'),
            'givenName' => __('Given name', 'librett-player-registry'),
            'given_name' => __('Given name', 'librett-player-registry'),
            'familyName' => __('Family name', 'librett-player-registry'),
            'family_name' => __('Family name', 'librett-player-registry'),
            'birthYear' => __('Birth year', 'librett-player-registry'),
            'birth_year' => __('Birth year', 'librett-player-registry'),
            'country' => __('Country', 'librett-player-registry'),
            'region' => __('Region', 'librett-player-registry'),
            'biography' => __('Biography', 'librett-player-registry'),
            'abbreviation' => __('Abbreviation', 'librett-player-registry'),
            'aliases' => __('Club aliases', 'librett-player-registry'),
            'memberships' => __('Club memberships', 'librett-player-registry'),
            'photo' => __('Photo', 'librett-player-registry'),
            default => $field,
        };
    }

    public function submit(): void
    {
        try {
            $actor = $this->guard->actor('librett_registry_publish_profiles');
            $this->guard->post('librett_registry_publication');
            $type = DraftRequest::text($_POST['type'] ?? null, 8);
            $id = new EntityId(DraftRequest::text($_POST['id'] ?? null, 36));
            $public = DraftRequest::counter($_POST['public_revision'] ?? null);
            $decision = DraftRequest::text($_POST['decision'] ?? null, 16);
            if ($decision === 'withdraw') {
                $this->withdraw->execute($actor, $type, $id, $public);
            } elseif ($decision === 'approve') {
                $rawFields = $_POST['fields'] ?? [];
                if (!is_array($rawFields) || count($rawFields) > 10 || !array_is_list($rawFields)) {
                    throw new InvalidArgumentException('Invalid field selection.');
                }
                $fields = array_map(static fn(mixed $field): string => DraftRequest::text($field, 32), $rawFields);
                $this->approve->execute(
                    $actor,
                    $type,
                    $id,
                    DraftRequest::counter($_POST['draft_revision'] ?? null),
                    $public,
                    DraftRequest::text($_POST['policy_version'] ?? null, 256),
                    $fields,
                    DraftRequest::text($_POST['evidence'] ?? null, 4000),
                    DraftRequest::text($_POST['age_status'] ?? 'not-applicable', 32),
                );
            } else {
                throw new InvalidArgumentException('Invalid publication decision.');
            }
        } catch (RegistryFailure|InvalidArgumentException) {
            wp_die(esc_html__('Publication was not changed. Reload and review revisions, policy, age status and referenced clubs/photo.', 'librett-player-registry'), '', ['response' => 409]);
        }
        wp_safe_redirect(admin_url('admin.php?page=librett-registry-publication'));
        exit;
    }
}
