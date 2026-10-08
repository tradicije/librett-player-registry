<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\SqlTable;

final class ImportSchema
{
    /** @return array<string, SqlTable> */
    public static function tables(): array
    {
        $uuid = 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL';
        $hash = 'char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL';
        return [
            'import_jobs' => new SqlTable(['request_uuid' => $uuid, 'actor_id' => 'bigint unsigned NOT NULL', 'created_at' => 'char(20) NOT NULL', 'payload' => 'longtext NOT NULL', 'payload_hash' => $hash, 'semantic_hash' => $hash, 'mappings' => 'longtext NOT NULL'], ['request_uuid']),
            'import_receipts' => new SqlTable(['request_uuid' => $uuid, 'actor_id' => 'bigint unsigned NOT NULL', 'payload_hash' => $hash, 'semantic_hash' => $hash, 'players_count' => 'int unsigned NOT NULL', 'clubs_count' => 'int unsigned NOT NULL'], ['request_uuid']),
            'import_mappings' => new SqlTable(['source_registry' => $uuid, 'entity_type' => 'varchar(8) NOT NULL', 'source_uuid' => $uuid, 'local_uuid' => $uuid, 'local_revision' => 'bigint unsigned NOT NULL', 'source_state' => 'varchar(12) NOT NULL'], ['source_registry','entity_type','source_uuid']),
            'import_lock' => new SqlTable(['singleton' => 'tinyint unsigned NOT NULL'], ['singleton']),
        ];
    }
}
