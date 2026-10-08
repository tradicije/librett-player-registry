<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use Closure;
use InvalidArgumentException;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use ValueError;

/** Shared form rendering; each module supplies its own application commands/readers. */
final readonly class DraftPage
{
    /**
     * @param array<string, array{label: string, limit: int, type: string}> $fields
     * @param Closure(Actor, string, int): list<DraftView> $search
     * @param Closure(Actor, EntityId): ?DraftView $find
     * @param Closure(Actor, ?EntityId, EditRevision, array<string, string>, DraftState): EntityId $save
     */
    public function __construct(
        private string $slug,
        private string $title,
        private array $fields,
        private Closure $search,
        private Closure $find,
        private Closure $save,
        private SchemaGate $schema,
        private Preflight $preflight,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            add_submenu_page('librett-registry', $this->title, $this->title, 'librett_registry_edit_profiles', $this->slug, $this->render(...));
        });
        add_action('admin_post_' . $this->slug, $this->submit(...));
    }

    public function render(): void
    {
        $actor = $this->actor();
        echo '<div class="wrap"><h1>' . esc_html($this->title) . '</h1>';
        try {
            $this->ready();
            $query = DraftRequest::text($_GET['search'] ?? '');
            $offset = DraftRequest::counter($_GET['offset'] ?? '0', 100000);
            $id = DraftRequest::text($_GET['entity'] ?? '', 36);
            $draft = $id === '' ? null : ($this->find)($actor, new EntityId($id));
            if ($id !== '' && $draft === null) {
                throw new RegistryFailure('not_found');
            }
            if (($_GET['saved'] ?? null) === '1') {
                echo '<div class="notice notice-success"><p>' . esc_html__('Private draft saved.', 'librett-player-registry') . '</p></div>';
            }
            echo '<p>' . esc_html__('These profiles are private drafts. Names may repeat; records are never merged automatically.', 'librett-player-registry') . '</p>';
            $this->editor($draft);
            echo '<h2>' . esc_html__('Search drafts', 'librett-player-registry') . '</h2><form method="get"><input type="hidden" name="page" value="' . esc_attr($this->slug) . '">';
            echo '<label for="librett-search">' . esc_html__('Name', 'librett-player-registry') . '</label> <input id="librett-search" name="search" maxlength="200" value="' . esc_attr($query) . '"> ';
            submit_button(__('Search', 'librett-player-registry'), 'secondary', 'submit', false);
            echo '</form><table class="widefat striped"><thead><tr><th>' . esc_html__('Name', 'librett-player-registry') . '</th><th>UUID</th><th>' . esc_html__('Status', 'librett-player-registry') . '</th></tr></thead><tbody>';
            $rows = ($this->search)($actor, $query, $offset);
            foreach ($rows as $row) {
                $url = add_query_arg(['page' => $this->slug, 'entity' => $row->id->value, 'search' => $query, 'offset' => $offset], admin_url('admin.php'));
                echo '<tr><td><a href="' . esc_url($url) . '">' . esc_html($row->fields['name']) . '</a></td><td><code>' . esc_html($row->id->value) . '</code></td><td>' . esc_html($this->stateLabel($row->state)) . '</td></tr>';
            }
            if ($rows === []) {
                echo '<tr><td colspan="3">' . esc_html__('No drafts found.', 'librett-player-registry') . '</td></tr>';
            }
            echo '</tbody></table><p>';
            if ($offset > 0) {
                $this->pageLink($query, max(0, $offset - 50), __('Previous', 'librett-player-registry'));
            }
            if (count($rows) === 50 && $offset < 100000) {
                $this->pageLink($query, min(100000, $offset + 50), __('Next', 'librett-player-registry'));
            }
            echo '</p>';
        } catch (RegistryFailure|InvalidArgumentException|ValueError) {
            echo '<p>' . esc_html__('Drafts are unavailable or the request is invalid. Check registry setup and the private-draft schema.', 'librett-player-registry') . '</p>';
            if (current_user_can('librett_registry_manage_settings')) {
                echo '<a href="' . esc_url(admin_url('admin.php?page=librett-registry-schema')) . '">' . esc_html__('Private-draft schema', 'librett-player-registry') . '</a>';
            }
        }
        echo '</div>';
    }

    public function submit(): void
    {
        $actor = $this->actor();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die(esc_html__('POST required.', 'librett-player-registry'), '', ['response' => 405]);
        }
        check_admin_referer($this->slug);
        try {
            $this->ready();
            $id = DraftRequest::text($_POST['entity'] ?? '', 36);
            $expected = new EditRevision(DraftRequest::counter($_POST['revision'] ?? null));
            $state = DraftState::from(DraftRequest::text($_POST['state'] ?? null, 16));
            $values = [];
            foreach ($this->fields as $key => $field) {
                $value = DraftRequest::text($_POST[$key] ?? '', $field['limit'] * 4 + 100);
                $values[$key] = $field['type'] === 'textarea' ? str_replace("\r\n", "\n", $value) : $value;
            }
            $saved = ($this->save)($actor, $id === '' ? null : new EntityId($id), $expected, $values, $state);
        } catch (InvalidArgumentException|ValueError) {
            wp_die(esc_html__('Invalid draft fields or revision. No changes were saved.', 'librett-player-registry'), '', ['response' => 400]);
        } catch (RegistryFailure $failure) {
            $conflict = $failure->errorCode === 'revision_conflict';
            $message = $conflict ? __('This draft changed. Reload it and review your changes before saving.', 'librett-player-registry')
                : __('The draft could not be saved. Check registry setup and storage.', 'librett-player-registry');
            wp_die(esc_html($message), '', ['response' => $conflict ? 409 : 503]);
        }
        wp_safe_redirect(add_query_arg(['page' => $this->slug, 'entity' => $saved->value, 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    private function editor(?DraftView $draft): void
    {
        echo '<h2>' . esc_html($draft === null ? __('New draft', 'librett-player-registry') : __('Edit draft', 'librett-player-registry')) . '</h2>';
        echo '<p><a href="' . esc_url(add_query_arg('page', $this->slug, admin_url('admin.php'))) . '">' . esc_html__('New draft', 'librett-player-registry') . '</a></p>';
        if ($draft !== null) {
            echo '<p><code>' . esc_html($draft->id->value) . '</code></p>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="' . esc_attr($this->slug) . '">';
        echo '<input type="hidden" name="entity" value="' . esc_attr($draft?->id->value ?? '') . '"><input type="hidden" name="revision" value="' . esc_attr((string) ($draft?->revision->value ?? 0)) . '">';
        wp_nonce_field($this->slug);
        echo '<table class="form-table"><tbody>';
        foreach ($this->fields as $key => $field) {
            $value = $draft?->fields[$key] ?? '';
            echo '<tr><th><label for="draft-' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th><td>';
            if ($field['type'] === 'textarea') {
                echo '<textarea class="large-text" rows="5" id="draft-' . esc_attr($key) . '" name="' . esc_attr($key) . '" maxlength="' . esc_attr((string) $field['limit']) . '">' . esc_textarea($value) . '</textarea>';
            } else {
                echo '<input class="regular-text" type="text" id="draft-' . esc_attr($key) . '" name="' . esc_attr($key) . '" maxlength="' . esc_attr((string) $field['limit']) . '" value="' . esc_attr($value) . '"' . ($key === 'name' ? ' required' : '') . '>';
            }
            echo '</td></tr>';
        }
        echo '<tr><th><label for="draft-state">' . esc_html__('Status', 'librett-player-registry') . '</label></th><td><select id="draft-state" name="state">';
        $selectedState = $draft === null ? DraftState::Active : $draft->state;
        foreach (DraftState::cases() as $state) {
            if ($draft === null && $state === DraftState::Archived) {
                continue;
            }
            echo '<option value="' . esc_attr($state->value) . '"' . selected($selectedState->value, $state->value, false) . '>' . esc_html($this->stateLabel($state)) . '</option>';
        }
        echo '</select></td></tr></tbody></table>';
        submit_button(__('Save private draft', 'librett-player-registry'));
        echo '</form>';
    }

    private function stateLabel(DraftState $state): string
    {
        return $state === DraftState::Active ? __('Active', 'librett-player-registry') : __('Archived', 'librett-player-registry');
    }

    private function pageLink(string $query, int $offset, string $label): void
    {
        echo '<a class="button" href="' . esc_url(add_query_arg(['page' => $this->slug, 'search' => $query, 'offset' => $offset], admin_url('admin.php'))) . '">' . esc_html($label) . '</a> ';
    }

    private function ready(): void
    {
        $this->preflight->assertReady();
        $this->schema->assertComplete();
    }

    private function actor(): Actor
    {
        if (!current_user_can('librett_registry_edit_profiles')) {
            wp_die(esc_html__('Permission denied.', 'librett-player-registry'), '', ['response' => 403]);
        }
        return new Actor(get_current_user_id(), ['librett_registry_edit_profiles']);
    }
}
