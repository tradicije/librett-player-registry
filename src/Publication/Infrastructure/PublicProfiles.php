<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SchemaGate;
use LibreTT\PlayerRegistry\Publication\Application\ExportSnapshot;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class PublicProfiles
{
    public function __construct(private ExportSnapshot $export, private SchemaGate $schema) {}

    public function register(): void
    {
        add_shortcode('librett_registry', $this->render(...));
    }

    public function render(): string
    {
        try {
            $this->schema->assertComplete();
            $snapshot = $this->export->execute();
            $query = DraftRequest::text($_GET['registry_search'] ?? '', 800);
            PlainText::assertValid($query, 200);
            $type = DraftRequest::text($_GET['registry_type'] ?? 'players', 8);
            if (!in_array($type, ['players', 'clubs'], true)) {
                throw new InvalidArgumentException('Invalid profile type.');
            }
            $id = DraftRequest::text($_GET['registry_id'] ?? '', 36);
            if ($id !== '') {
                new EntityId($id);
            }
            $offset = DraftRequest::counter($_GET['registry_offset'] ?? '0', 100000);
            $html = '<section class="librett-registry"><h2>' . esc_html($snapshot->registry_name) . '</h2><form method="get"><label>' . esc_html__('Search public profiles', 'librett-player-registry') . ' <input name="registry_search" value="' . esc_attr($query) . '" maxlength="200"></label><select name="registry_type"><option value="players"' . selected($type, 'players', false) . '>' . esc_html__('Players', 'librett-player-registry') . '</option><option value="clubs"' . selected($type, 'clubs', false) . '>' . esc_html__('Clubs', 'librett-player-registry') . '</option></select><button>' . esc_html__('Search', 'librett-player-registry') . '</button></form>';
            $photos = [];
            foreach ($snapshot->media as $photo) {
                $photos[$photo->id] = $photo;
            }
            $clubs = [];
            foreach ($snapshot->clubs as $club) {
                $clubs[$club->id] = $club;
            }
            $memberships = [];
            foreach ($snapshot->memberships as $membership) {
                $memberships[$membership->player_id][] = $membership->club_id;
            }
            $records = [];
            foreach (($type === 'players' ? $snapshot->players : $snapshot->clubs) as $record) {
                $name = $record->display_name ?? $record->name;
                if (!is_string($name)) {
                    throw new RegistryFailure('invalid_public_projection');
                }
                if (($id === '' && mb_stripos($name, $query) !== false) || $record->id === $id) {
                    $records[] = $record;
                }
            }
            foreach (array_slice($records, $id === '' ? $offset : 0, 50) as $record) {
                $url = add_query_arg(['registry_type' => $type, 'registry_id' => $record->id], get_permalink() ?: home_url('/'));
                $html .= '<article><h3><a href="' . esc_url($url) . '">' . esc_html(is_string($record->display_name ?? $record->name) ? ($record->display_name ?? $record->name) : '') . '</a></h3>';
                if (isset($record->photo_id) && is_string($record->photo_id) && isset($photos[$record->photo_id])) {
                    $photo = $photos[$record->photo_id];
                    $html .= '<figure><img style="max-width:240px;height:auto" alt="" src="' . esc_url($photo->content_url) . '"><figcaption>' . esc_html($photo->attribution) . '</figcaption></figure>';
                }
                foreach (['given_name', 'family_name', 'country', 'region', 'birth_year', 'abbreviation'] as $field) {
                    if (isset($record->$field) && (is_string($record->$field) || is_int($record->$field))) {
                        $html .= '<p>' . esc_html((string) $record->$field) . '</p>';
                    }
                }
                if (isset($record->biography) && is_string($record->biography)) {
                    $html .= '<p>' . nl2br(esc_html($record->biography)) . '</p>';
                }
                if ($type === 'players') {
                    foreach ($memberships[$record->id] ?? [] as $clubId) {
                        $club = $clubs[$clubId];
                        $html .= '<p><a href="' . esc_url(add_query_arg(['registry_type' => 'clubs', 'registry_id' => $club->id], get_permalink() ?: home_url('/'))) . '">' . esc_html($club->name) . '</a></p>';
                    }
                }
                $html .= '</article>';
            }
            if ($records === []) {
                $html .= '<p>' . esc_html__('No public profile found.', 'librett-player-registry') . '</p>';
            }
            if ($id === '' && count($records) > $offset + 50) {
                $html .= '<a href="' . esc_url(add_query_arg(['registry_type' => $type, 'registry_search' => $query, 'registry_offset' => $offset + 50], get_permalink() ?: home_url('/'))) . '">' . esc_html__('Next profiles', 'librett-player-registry') . '</a>';
            }
            return $html . '</section>';
        } catch (RegistryFailure|InvalidArgumentException) {
            return '<p>' . esc_html__('Public catalogue is unavailable.', 'librett-player-registry') . '</p>';
        }
    }
}
