<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\SqlTable;

final class RelationsSchema
{
    /** @return array<string, SqlTable> */
    public static function tables(): array
    {
        $uuid = 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL';
        return [
            'club_aliases' => new SqlTable(['club_uuid' => $uuid, 'alias' => 'varchar(200) COLLATE utf8mb4_bin NOT NULL'], ['club_uuid', 'alias']),
            'memberships' => new SqlTable(['player_uuid' => $uuid, 'club_uuid' => $uuid], ['player_uuid', 'club_uuid']),
        ];
    }
}
