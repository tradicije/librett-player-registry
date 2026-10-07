<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryRepository;
use LibreTT\PlayerRegistry\RegistryIdentity\Domain\Registry;
use LibreTT\PlayerRegistry\RegistryIdentity\Domain\RegistryId;

final readonly class WordPressRegistryRepository implements RegistryRepository
{
    public function __construct(private Database $db) {}

    public function current(): ?Registry
    {
        $rows = $this->db->rows('SELECT registry_uuid, name, role FROM ' . $this->db->table('identity') . ' WHERE singleton = 1');
        if (count($rows) !== 1) {
            throw new RegistryFailure('schema_unavailable');
        }
        $row = $rows[0];
        if ($row['registry_uuid'] === null && $row['name'] === null && $row['role'] === null) {
            return null;
        }
        if ($row['role'] !== 'primary') {
            throw new RegistryFailure('unsupported_registry_role');
        }
        return new Registry(new RegistryId((string) $row['registry_uuid']), (string) $row['name']);
    }

    public function createIfUnconfigured(Registry $registry, int $actorId): void
    {
        $this->db->rows('SELECT singleton FROM ' . $this->db->table('identity') . ' WHERE singleton = 1 FOR UPDATE');
        $sql = $this->db->prepare(
            'UPDATE %i SET registry_uuid = %s, name = %s, role = %s WHERE singleton = 1 AND registry_uuid IS NULL AND name IS NULL AND role IS NULL',
            trim($this->db->table('identity'), '`'),
            $registry->id->value,
            $registry->name,
            'primary',
        );
        if ($this->db->execute($sql) !== 1) {
            throw new RegistryFailure('identity_conflict');
        }
        $audit = $this->db->prepare(
            'INSERT INTO %i (actor_id, action) VALUES (%d, %s)',
            trim($this->db->table('identity_audit'), '`'),
            $actorId,
            'registry_created',
        );
        $this->db->execute($audit);
    }
}
