<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\SqlTable;

final class PublicationSchema
{
    /** @return array<string, SqlTable> */
    public static function tables(): array
    {
        $uuid = 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL';
        return [
            'publication_policy' => new SqlTable(['singleton' => 'tinyint unsigned NOT NULL', 'edit_revision' => 'bigint unsigned NOT NULL', 'payload' => 'longtext NOT NULL'], ['singleton']),
            'publication_head' => new SqlTable(['singleton' => 'tinyint unsigned NOT NULL', 'checkpoint' => 'bigint unsigned NOT NULL'], ['singleton']),
            'public_records' => new SqlTable(['entity_type' => 'varchar(8) NOT NULL', 'entity_uuid' => $uuid, 'payload' => 'longtext NOT NULL'], ['entity_type','entity_uuid']),
            'public_ledger' => new SqlTable(['entity_type' => 'varchar(8) NOT NULL', 'entity_uuid' => $uuid, 'revision' => 'bigint unsigned NOT NULL', 'checkpoint' => 'bigint unsigned NOT NULL', 'state' => 'varchar(12) NOT NULL'], ['entity_type','entity_uuid']),
            'publication_audit' => new SqlTable(['audit_uuid' => $uuid, 'entity_type' => 'varchar(8) NOT NULL', 'entity_uuid' => $uuid, 'actor_id' => 'bigint unsigned NOT NULL', 'policy_version' => 'varchar(64) NOT NULL', 'evidence' => 'text NOT NULL', 'action' => 'varchar(24) NOT NULL', 'created_at' => 'timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)'], ['audit_uuid']),
            'publication_events' => new SqlTable(['checkpoint' => 'bigint unsigned NOT NULL', 'entity_type' => 'varchar(8) NOT NULL', 'entity_uuid' => $uuid, 'revision' => 'bigint unsigned NOT NULL', 'action' => 'varchar(12) NOT NULL'], ['checkpoint']),
        ];
    }
}
