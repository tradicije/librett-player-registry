<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Infrastructure;

use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftRepository;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class WordPressClubDraftRepository implements ClubDraftRepository
{
    public function __construct(private Database $db) {}

    public function find(EntityId $id): ?ClubDraft
    {
        $rows = $this->db->rows($this->db->prepare('SELECT * FROM %i WHERE entity_uuid = %s', $this->table(), $id->value));
        return $rows === [] ? null : $this->hydrate($rows[0]);
    }

    public function search(string $query, int $offset): array
    {
        $rows = $this->db->rows($this->db->prepare(
            'SELECT * FROM %i WHERE name LIKE %s ORDER BY name, entity_uuid LIMIT 50 OFFSET %d',
            $this->table(),
            '%' . $this->db->connection->esc_like($query) . '%',
            $offset,
        ));
        return array_map($this->hydrate(...), $rows);
    }

    public function save(ClubDraft $draft, EditRevision $expected, int $actorId): void
    {
        $values = [
            $draft->id->value,
            $draft->revision->value,
            $draft->state->value,
            $draft->data->name,
            $draft->data->country,
            $draft->data->region,
            $draft->data->abbreviation,
        ];
        $action = 'created';
        if ($expected->value === 0) {
            $this->db->execute($this->db->prepare(
                'INSERT INTO %i (entity_uuid, edit_revision, state, name, country, region, abbreviation) VALUES (%s, %s, %s, %s, %s, %s, %s)',
                $this->table(),
                ...$values,
            ));
        } else {
            $rows = $this->db->rows($this->db->prepare('SELECT edit_revision, state FROM %i WHERE entity_uuid = %s FOR UPDATE', $this->table(), $draft->id->value));
            if ($rows === [] || (int) $rows[0]['edit_revision'] !== $expected->value) {
                throw new RegistryFailure('revision_conflict');
            }
            $action = $rows[0]['state'] === $draft->state->value ? 'updated'
                : ($draft->state === DraftState::Archived ? 'archived' : 'restored');
            if ($this->db->execute($this->db->prepare(
                'UPDATE %i SET edit_revision = %s, state = %s, name = %s, country = %s, region = %s, abbreviation = %s WHERE entity_uuid = %s AND edit_revision = %s',
                $this->table(),
                ...[...array_slice($values, 1), $draft->id->value, $expected->value],
            )) !== 1) {
                throw new RegistryFailure('revision_conflict');
            }
        }
        $this->db->execute($this->db->prepare(
            'INSERT INTO %i (entity_uuid, edit_revision, actor_id, action) VALUES (%s, %s, %d, %s)',
            trim($this->db->table('clubs_audit'), '`'),
            $draft->id->value,
            $draft->revision->value,
            $actorId,
            $action,
        ));
    }

    private function table(): string
    {
        return trim($this->db->table('clubs'), '`');
    }

    /** @param array<string, int|string|null> $row */
    private function hydrate(array $row): ClubDraft
    {
        return new ClubDraft(
            new EntityId((string) $row['entity_uuid']),
            new EditRevision((int) $row['edit_revision']),
            new ClubData(
                name: (string) $row['name'],
                country: (string) $row['country'],
                region: (string) $row['region'],
                abbreviation: (string) $row['abbreviation'],
            ),
            DraftState::from((string) $row['state']),
        );
    }
}
