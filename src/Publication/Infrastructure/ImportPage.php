<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdminGuard;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\UploadRequest;
use LibreTT\PlayerRegistry\Publication\Application\ConfirmImport;
use LibreTT\PlayerRegistry\Publication\Application\ImportStore;
use LibreTT\PlayerRegistry\Publication\Application\SnapshotValidator;
use LibreTT\PlayerRegistry\Publication\Application\StageImport;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ImportPage
{
    public function __construct(
        private StageImport $stage,
        private ConfirmImport $confirm,
        private ImportStore $store,
        private SnapshotValidator $validator,
        private AdminGuard $guard,
    ) {}

    private function actor(): \LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor
    {
        $actor = $this->guard->actor('librett_registry_import');
        if (!current_user_can('librett_registry_edit_profiles')) {
            wp_die(esc_html__('Profile editing permission is required.', 'librett-player-registry'), '', ['response' => 403]);
        }
        return new \LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor($actor->id, ['librett_registry_import','librett_registry_edit_profiles']);
    }

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('JSON import', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_import', 'librett-registry-import', $this->render(...));
        });
        add_action('admin_post_librett_registry_import', $this->submit(...));
    }

    public function render(): void
    {
        $actor = $this->actor();
        echo '<div class="wrap"><h1>' . esc_html__('JSON import', 'librett-player-registry') . '</h1><p>' . esc_html__('Import an unsigned public snapshot as private drafts. Names never create automatic matches. The source registry does not become the authority of this registry. Photos are retained as source descriptors and are not downloaded or published.', 'librett-player-registry') . '</p>';
        AdminGuard::form('librett_registry_import', true);
        AdminGuard::hidden('decision', 'stage');
        echo '<p><label>' . esc_html__('Snapshot JSON file, maximum 32 MiB (server upload limits also apply)', 'librett-player-registry') . '<br><input type="file" name="snapshot" accept=".json,application/json" required></label></p><p><label>' . esc_html__('Optional explicit mappings: player or club, source UUID, existing local UUID; one per line. Leave empty to create independent identities or reuse saved provenance.', 'librett-player-registry') . '<br><textarea name="mappings" rows="5" cols="100"></textarea></label></p>';
        submit_button(__('Validate and preview', 'librett-player-registry'));
        echo '</form>';
        foreach ($this->store->pending($actor->id) as $pending) {
            echo '<p><a href="' . esc_url(add_query_arg(['page' => 'librett-registry-import','job' => $pending['id']], admin_url('admin.php'))) . '">' . esc_html($pending['created_at'] . ' · ' . $pending['id']) . '</a></p>';
        }
        if (isset($_GET['job'])) {
            try {
                $job = $this->store->job(new EntityId(DraftRequest::text($_GET['job'], 36)));
                if ($job === null || $job->actorId !== $actor->id) {
                    throw new RegistryFailure('invalid_import_job');
                }
                $snapshot = $this->validator->decode($job->payload);
                echo '<h2>' . esc_html__('Review before confirmation', 'librett-player-registry') . '</h2><p>' . esc_html($snapshot->registry_name . ' · ' . $snapshot->registry_id) . '</p><p>' . esc_html(sprintf(__('Players: %d; clubs: %d; memberships: %d; source withdrawals: %d.', 'librett-player-registry'), count($snapshot->players), count($snapshot->clubs), count($snapshot->memberships), count($snapshot->tombstones))) . '</p><p>' . esc_html__('Existing mapped drafts receive supplied fields and current source club links; omitted optional fields retain local values. Archived records remain archived. Source withdrawals mark provenance and do not delete local records. Confirmation expires after one hour or relevant local edits. Public approvals remain unchanged.', 'librett-player-registry') . '</p>';
                $offset = DraftRequest::counter($_GET['offset'] ?? '0', 100000);
                echo '<table class="widefat"><thead><tr><th>' . esc_html__('Source record', 'librett-player-registry') . '</th><th>' . esc_html__('Local target and expected revision', 'librett-player-registry') . '</th></tr></thead><tbody>';
                $byId = [];
                foreach ($snapshot->clubs as $club) {
                    $byId['club:' . $club->id] = $club;
                }
                foreach ($snapshot->players as $player) {
                    $byId['player:' . $player->id] = $player;
                }
                foreach (array_slice($job->mappings, $offset, 50) as $map) {
                    echo '<tr><td><pre>' . esc_html(json_encode($byId[$map->type . ':' . $map->source->value], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td><td>' . esc_html($map->local->value . ' · ' . (string) $map->expected) . '</td></tr>';
                }
                echo '</tbody></table>';
                if (count($job->mappings) > $offset + 50) {
                    echo '<p><a href="' . esc_url(add_query_arg(['page' => 'librett-registry-import', 'job' => $job->id->value, 'offset' => $offset + 50], admin_url('admin.php'))) . '">' . esc_html__('Next preview records', 'librett-player-registry') . '</a></p>';
                }
                AdminGuard::form('librett_registry_import');
                AdminGuard::hidden('job', $job->id->value);
                AdminGuard::hidden('payload_hash', $job->payloadHash);
                echo '<p><label><input type="checkbox" name="reviewed" value="1" required> ' . esc_html__('I reviewed the proposed records and mappings and authorize these private draft changes.', 'librett-player-registry') . '</label></p><button class="button button-primary" name="decision" value="confirm">' . esc_html__('Confirm import', 'librett-player-registry') . '</button></form>';
                AdminGuard::form('librett_registry_import');
                AdminGuard::hidden('job', $job->id->value);
                echo '<p><button class="button" name="decision" value="cancel">' . esc_html__('Cancel and remove staged file', 'librett-player-registry') . '</button></p></form>';
            } catch (RegistryFailure|InvalidArgumentException) {
                echo '<p>' . esc_html__('Preview unavailable.', 'librett-player-registry') . '</p>';
            }
        }
        echo '</div>';
    }

    public function submit(): void
    {
        $url = admin_url('admin.php?page=librett-registry-import');
        try {
            $actor = $this->actor();
            $this->guard->post('librett_registry_import');
            $decision = DraftRequest::text($_POST['decision'] ?? null, 16);
            if ($decision === 'stage') {
                $payload = UploadRequest::bytes('snapshot', 33554432) ?? throw new InvalidArgumentException('Snapshot required.');
                $raw = trim(DraftRequest::text($_POST['mappings'] ?? '', 3000000));
                $maps = [];
                foreach ($raw === '' ? [] : explode("\n", $raw) as $line) {
                    $parts = preg_split('/\s+/', trim($line));
                    if ($parts === false || count($parts) !== 3 || !in_array($parts[0], ['player','club'], true)) {
                        throw new InvalidArgumentException('Invalid explicit mapping.');
                    }
                    $key = $parts[0] . ':' . (new EntityId($parts[1]))->value;
                    if (isset($maps[$key])) {
                        throw new InvalidArgumentException('Duplicate mapping.');
                    }
                    $maps[$key] = new EntityId($parts[2]);
                }
                $job = $this->stage->execute($actor, $payload, $maps);
                $url = add_query_arg('job', $job->id->value, $url);
            } elseif ($decision === 'confirm') {
                if (($_POST['reviewed'] ?? null) !== '1') {
                    throw new InvalidArgumentException('Review required.');
                }
                $this->confirm->execute($actor, new EntityId(DraftRequest::text($_POST['job'] ?? null, 36)), DraftRequest::text($_POST['payload_hash'] ?? null, 64));
            } elseif ($decision === 'cancel') {
                $this->store->cancel(new EntityId(DraftRequest::text($_POST['job'] ?? null, 36)), $actor->id);
            } else {
                throw new InvalidArgumentException('Invalid import action.');
            }
        } catch (RegistryFailure|InvalidArgumentException) {
            wp_die(esc_html__('Import did not complete. Check format, mappings, permissions, preview age and current draft revisions. Cancel old previews if the staging limit was reached.', 'librett-player-registry'), '', ['response' => 409]);
        }
        wp_safe_redirect($url);
        exit;
    }
}
