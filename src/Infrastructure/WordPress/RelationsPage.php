<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Application\ChangeMemberships;
use LibreTT\PlayerRegistry\Clubs\Application\ClubAliases;
use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftReader;
use LibreTT\PlayerRegistry\Clubs\Application\MembershipRepository;
use LibreTT\PlayerRegistry\Clubs\Application\SetClubAliases;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class RelationsPage
{
    public function __construct(
        private PlayerDraftReader $players,
        private ClubDraftReader $clubs,
        private MembershipRepository $memberships,
        private ClubAliases $aliases,
        private ChangeMemberships $change,
        private SetClubAliases $setAliases,
        private SchemaGate $schema,
        private Preflight $preflight,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('Memberships, aliases and duplicate review', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_edit_profiles', 'librett-registry-relations', $this->render(...));
        });
        add_action('admin_post_librett_registry_relations', $this->submit(...));
    }

    private function actor(): Actor
    {
        if (!current_user_can('librett_registry_edit_profiles')) {
            wp_die(esc_html__('Permission denied.', 'librett-player-registry'), '', ['response' => 403]);
        }
        $this->preflight->assertReady();
        $this->schema->assertComplete();
        return new Actor(get_current_user_id(), ['librett_registry_edit_profiles']);
    }

    public function render(): void
    {
        try {
            $actor = $this->actor();
            $query = DraftRequest::text($_GET['q'] ?? '', 800);
            $offset = DraftRequest::counter($_GET['offset'] ?? '0', 100000);
            $players = $this->players->search($actor, $query, $offset);
            $clubs = $this->clubs->search($actor, $query, $offset);
            echo '<div class="wrap"><h1>' . esc_html__('Memberships, aliases and duplicate review', 'librett-player-registry') . '</h1>';
            echo '<p>' . esc_html__('Compare identifiers and profile details before changing records. Matching names do not merge people or clubs. Search results include archived drafts.', 'librett-player-registry') . '</p>';
            echo '<form method="get"><input type="hidden" name="page" value="librett-registry-relations"><label>' . esc_html__('Search candidates by name', 'librett-player-registry') . ' <input name="q" value="' . esc_attr($query) . '" maxlength="200"></label>';
            submit_button(__('Search', 'librett-player-registry'), 'secondary', '', false);
            echo '</form>';
            foreach ($players as $player) {
                echo '<h2><a href="' . esc_url(admin_url('admin.php?page=librett-registry-players&id=' . $player->id->value)) . '">' . esc_html($player->data->name) . '</a></h2><p>' . esc_html($player->id->value . ' · ' . $player->state->value . ' · ' . $player->data->country . ' · ' . ($player->data->birthYear ?? '')) . '</p>';
                if ($player->state !== DraftState::Active) {
                    continue;
                }
                $this->form('memberships', $player->id, $player->revision);
                $selected = array_map(static fn(EntityId $id): string => $id->value, $this->memberships->forPlayer($player->id));
                echo '<label>' . esc_html__('Club identifiers, one per line (maximum 100). Search club drafts to find identifiers.', 'librett-player-registry') . '<br><textarea name="values" rows="4" cols="60">' . esc_textarea(implode("\n", $selected)) . '</textarea></label>';
                submit_button(__('Save memberships', 'librett-player-registry'));
                echo '</form>';
            }
            foreach ($clubs as $club) {
                echo '<h2><a href="' . esc_url(admin_url('admin.php?page=librett-registry-clubs&id=' . $club->id->value)) . '">' . esc_html($club->data->name) . '</a></h2><p>' . esc_html($club->id->value . ' · ' . $club->state->value . ' · ' . $club->data->country) . '</p>';
                $this->form('aliases', $club->id, $club->revision);
                echo '<label>' . esc_html__('Aliases, one per line (maximum 20)', 'librett-player-registry') . '<br><textarea name="values" rows="4" cols="60">' . esc_textarea(implode("\n", $this->aliases->forClub($club->id))) . '</textarea></label>';
                submit_button(__('Save aliases', 'librett-player-registry'));
                echo '</form>';
            }
            echo '<a href="' . esc_url(add_query_arg(['page' => 'librett-registry-relations', 'q' => $query, 'offset' => min(100000, $offset + 50)], admin_url('admin.php'))) . '">' . esc_html__('Next candidates', 'librett-player-registry') . '</a></div>';
        } catch (RegistryFailure|InvalidArgumentException) {
            wp_die(esc_html__('Registry unavailable or invalid request. Check setup and schema.', 'librett-player-registry'), '', ['response' => 400]);
        }
    }

    private function form(string $kind, EntityId $id, EditRevision $revision): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="librett_registry_relations"><input type="hidden" name="kind" value="' . esc_attr($kind) . '"><input type="hidden" name="id" value="' . esc_attr($id->value) . '"><input type="hidden" name="revision" value="' . esc_attr((string) $revision->value) . '">';
        wp_nonce_field('librett_registry_relations');
    }

    public function submit(): void
    {
        try {
            $actor = $this->actor();
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                wp_die(esc_html__('POST required.', 'librett-player-registry'), '', ['response' => 405]);
            }
            check_admin_referer('librett_registry_relations');
            $id = new EntityId(DraftRequest::text($_POST['id'] ?? null, 36));
            $revision = new EditRevision(DraftRequest::counter($_POST['revision'] ?? null));
            $raw = trim(DraftRequest::text($_POST['values'] ?? '', 20000));
            $values = $raw === '' ? [] : explode("\n", str_replace("\r", '', $raw));
            $kind = DraftRequest::text($_POST['kind'] ?? null, 20);
            if ($kind === 'memberships') {
                $this->change->execute($actor, $id, $revision, array_map(static fn(string $value): EntityId => new EntityId(trim($value)), $values));
            } elseif ($kind === 'aliases') {
                $this->setAliases->execute($actor, $id, $revision, array_map(trim(...), $values));
            } else {
                throw new InvalidArgumentException('Unknown relation.');
            }
        } catch (RegistryFailure|InvalidArgumentException $failure) {
            wp_die(esc_html__('Changes could not be saved. Reload the current draft and check the supplied values.', 'librett-player-registry'), '', ['response' => $failure instanceof RegistryFailure && $failure->errorCode === 'revision_conflict' ? 409 : 400]);
        }
        wp_safe_redirect(admin_url('admin.php?page=librett-registry-relations'));
        exit;
    }
}
