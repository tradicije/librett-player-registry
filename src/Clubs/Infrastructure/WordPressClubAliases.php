<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Infrastructure;

use LibreTT\PlayerRegistry\Clubs\Application\ClubAliases;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class WordPressClubAliases implements ClubAliases
{
    public function __construct(private Database $db) {}

    public function forClub(EntityId $club): array
    {
        return array_map(static fn(array $row): string => (string) $row['alias'], $this->db->rows($this->db->prepare('SELECT alias FROM %i WHERE club_uuid = %s ORDER BY alias', trim($this->db->table('club_aliases'), '`'), $club->value)));
    }

    public function replace(EntityId $club, array $aliases): void
    {
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE club_uuid = %s', trim($this->db->table('club_aliases'), '`'), $club->value));
        foreach ($aliases as $alias) {
            $this->db->execute($this->db->prepare('INSERT INTO %i (club_uuid, alias) VALUES (%s, %s)', trim($this->db->table('club_aliases'), '`'), $club->value, $alias));
        }
    }
}
