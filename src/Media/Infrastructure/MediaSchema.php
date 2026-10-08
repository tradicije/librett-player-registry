<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\SqlTable;

final class MediaSchema
{
    /** @return array<string, SqlTable> */
    public static function tables(): array
    {
        $uuid = 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL';
        return [
            'media_assets' => new SqlTable(['entity_uuid' => $uuid, 'storage_key' => 'varchar(48) NOT NULL', 'digest' => 'char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL', 'mime' => 'varchar(16) NOT NULL', 'byte_length' => 'int unsigned NOT NULL', 'width' => 'smallint unsigned NOT NULL', 'height' => 'smallint unsigned NOT NULL', 'attribution' => 'varchar(500) NOT NULL', 'rights_evidence' => 'text NOT NULL'], ['entity_uuid']),
            'player_photos' => new SqlTable(['player_uuid' => $uuid, 'media_uuid' => $uuid], ['player_uuid']),
            'media_audit' => new SqlTable(['audit_uuid' => $uuid, 'player_uuid' => $uuid, 'actor_id' => 'bigint unsigned NOT NULL', 'action' => 'varchar(24) NOT NULL', 'created_at' => 'timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)'], ['audit_uuid']),
        ];
    }
}
