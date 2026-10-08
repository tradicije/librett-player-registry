<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Infrastructure;

use LibreTT\PlayerRegistry\Clubs\Application\MembershipRepository;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class WordPressMembershipRepository implements MembershipRepository
{
    public function __construct(private Database $db) {}

    public function forPlayer(EntityId $player): array
    {
        $rows = $this->db->rows($this->db->prepare('SELECT club_uuid FROM %i WHERE player_uuid = %s ORDER BY club_uuid', trim($this->db->table('memberships'), '`'), $player->value));
        return array_map(static fn(array $row): EntityId => new EntityId((string) $row['club_uuid']), $rows);
    }

    public function playersInClub(EntityId $club): array
    {
        $rows = $this->db->rows($this->db->prepare('SELECT player_uuid FROM %i WHERE club_uuid = %s ORDER BY player_uuid', trim($this->db->table('memberships'), '`'), $club->value));
        return array_map(static fn(array $row): EntityId => new EntityId((string) $row['player_uuid']), $rows);
    }

    public function removeClub(EntityId $club): void
    {
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE club_uuid = %s', trim($this->db->table('memberships'), '`'), $club->value));
    }

    public function replace(EntityId $player, array $clubs, int $actorId): void
    {
        foreach ($clubs as $club) {
            $row = $this->db->rows($this->db->prepare('SELECT state FROM %i WHERE entity_uuid = %s FOR UPDATE', trim($this->db->table('clubs'), '`'), $club->value));
            if ($row === [] || $row[0]['state'] !== 'active') {
                throw new RegistryFailure('invalid_data');
            }
        }
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE player_uuid = %s', trim($this->db->table('memberships'), '`'), $player->value));
        foreach ($clubs as $club) {
            $this->db->execute($this->db->prepare('INSERT INTO %i (player_uuid, club_uuid) VALUES (%s, %s)', trim($this->db->table('memberships'), '`'), $player->value, $club->value));
            $this->db->execute($this->db->prepare('INSERT INTO %i (entity_uuid, edit_revision, actor_id, action) SELECT entity_uuid, edit_revision, %d, %s FROM %i WHERE entity_uuid = %s', trim($this->db->table('clubs_audit'), '`'), $actorId, 'membership_set', trim($this->db->table('clubs'), '`'), $club->value));
        }
    }
}
