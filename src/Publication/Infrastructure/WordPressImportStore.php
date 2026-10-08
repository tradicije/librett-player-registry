<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Publication\Application\ImportStore;
use LibreTT\PlayerRegistry\Publication\Domain\ImportJob;
use LibreTT\PlayerRegistry\Publication\Domain\ImportMapping;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class WordPressImportStore implements ImportStore
{
    public function __construct(private Database $db) {}

    private function table(string $name): string
    {
        return trim($this->db->table($name), '`');
    }

    private function lock(): void
    {
        $this->db->execute('INSERT IGNORE INTO ' . $this->db->table('import_lock') . ' (singleton) VALUES (1)');
        $this->db->rows('SELECT singleton FROM ' . $this->db->table('import_lock') . ' WHERE singleton = 1 FOR UPDATE');
    }

    public function mapping(EntityId $registry, string $type, EntityId $source): ?array
    {
        $rows = $this->db->rows($this->db->prepare('SELECT local_uuid, local_revision FROM %i WHERE source_registry = %s AND entity_type = %s AND source_uuid = %s', $this->table('import_mappings'), $registry->value, $type, $source->value));
        return $rows === [] ? null : ['local' => new EntityId((string) $rows[0]['local_uuid']), 'revision' => (int) $rows[0]['local_revision']];
    }

    public function stage(ImportJob $job): void
    {
        $this->lock();
        $existing = $this->job($job->id);
        if ($existing !== null) {
            if ($existing->actorId !== $job->actorId || !hash_equals($existing->payloadHash, $job->payloadHash) || !hash_equals($existing->semanticHash, $job->semanticHash)) {
                throw new RegistryFailure('request_reused');
            }
            return;
        }
        $count = $this->db->rows($this->db->prepare('SELECT COUNT(*) AS total FROM %i WHERE actor_id = %d', $this->table('import_jobs'), $job->actorId));
        if ((int) $count[0]['total'] >= 5) {
            throw new RegistryFailure('staging_limit');
        }
        $maps = array_map(static fn(ImportMapping $map): array => ['type' => $map->type, 'source' => $map->source->value, 'local' => $map->local->value, 'expected' => (string) $map->expected], $job->mappings);
        $this->db->execute($this->db->prepare('INSERT INTO %i (request_uuid, actor_id, created_at, payload, payload_hash, semantic_hash, mappings) VALUES (%s, %d, %s, %s, %s, %s, %s)', $this->table('import_jobs'), $job->id->value, $job->actorId, $job->createdAt, $job->payload, $job->payloadHash, $job->semanticHash, json_encode($maps, JSON_THROW_ON_ERROR)));
    }

    public function job(EntityId $id): ?ImportJob
    {
        $rows = $this->db->rows($this->db->prepare('SELECT * FROM %i WHERE request_uuid = %s', $this->table('import_jobs'), $id->value));
        if ($rows === []) {
            return null;
        }
        $row = $rows[0];
        $raw = json_decode((string) $row['mappings'], false, 4, JSON_THROW_ON_ERROR);
        if (!is_array($raw)) {
            throw new RegistryFailure('invalid_import_job');
        }
        $maps = [];
        foreach ($raw as $map) {
            if (!$map instanceof \stdClass || !is_string($map->type) || !is_string($map->source) || !is_string($map->local) || !is_string($map->expected)) {
                throw new RegistryFailure('invalid_import_job');
            }
            $maps[] = new ImportMapping($map->type, new EntityId($map->source), new EntityId($map->local), (int) $map->expected);
        }
        return new ImportJob($id, (int) $row['actor_id'], (string) $row['created_at'], (string) $row['payload'], (string) $row['payload_hash'], (string) $row['semantic_hash'], $maps);
    }

    public function receipt(EntityId $request): ?array
    {
        $this->lock();
        $rows = $this->db->rows($this->db->prepare('SELECT * FROM %i WHERE request_uuid = %s', $this->table('import_receipts'), $request->value));
        return $rows === [] ? null : ['actor_id' => (int) $rows[0]['actor_id'], 'payload_hash' => (string) $rows[0]['payload_hash'], 'semantic_hash' => (string) $rows[0]['semantic_hash'], 'players' => (int) $rows[0]['players_count'], 'clubs' => (int) $rows[0]['clubs_count']];
    }

    public function repeated(string $semanticHash): ?array
    {
        $this->lock();
        $rows = $this->db->rows($this->db->prepare('SELECT players_count, clubs_count FROM %i WHERE semantic_hash = %s LIMIT 1', $this->table('import_receipts'), $semanticHash));
        return $rows === [] ? null : ['players' => (int) $rows[0]['players_count'], 'clubs' => (int) $rows[0]['clubs_count']];
    }

    public function complete(ImportJob $job, EntityId $registry, int $players, int $clubs): void
    {
        $this->lock();
        foreach ($job->mappings as $map) {
            $this->db->execute($this->db->prepare('INSERT INTO %i (source_registry, entity_type, source_uuid, local_uuid, local_revision, source_state) VALUES (%s, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE local_uuid = VALUES(local_uuid), local_revision = VALUES(local_revision), source_state = VALUES(source_state)', $this->table('import_mappings'), $registry->value, $map->type, $map->source->value, $map->local->value, (string) $map->expected, 'live'));
        }
        $data = json_decode($job->payload, false, 8, JSON_THROW_ON_ERROR);
        if (!$data instanceof \stdClass || !is_array($data->tombstones)) {
            throw new RegistryFailure('invalid_import_job');
        }
        foreach ($data->tombstones as $tombstone) {
            if (!$tombstone instanceof \stdClass || !is_string($tombstone->entity_type) || !is_string($tombstone->entity_id)) {
                throw new RegistryFailure('invalid_import_job');
            }
            $this->db->execute($this->db->prepare('UPDATE %i SET source_state = %s WHERE source_registry = %s AND entity_type = %s AND source_uuid = %s', $this->table('import_mappings'), 'withdrawn', $registry->value, $tombstone->entity_type, $tombstone->entity_id));
        }
        $this->db->execute($this->db->prepare('INSERT INTO %i (request_uuid, actor_id, payload_hash, semantic_hash, players_count, clubs_count) VALUES (%s, %d, %s, %s, %d, %d)', $this->table('import_receipts'), $job->id->value, $job->actorId, $job->payloadHash, $job->semanticHash, $players, $clubs));
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE request_uuid = %s', $this->table('import_jobs'), $job->id->value));
    }

    public function pending(int $actorId): array
    {
        $rows = $this->db->rows($this->db->prepare('SELECT request_uuid, created_at FROM %i WHERE actor_id = %d ORDER BY created_at LIMIT 5', $this->table('import_jobs'), $actorId));
        return array_map(static fn(array $row): array => ['id' => (string) $row['request_uuid'], 'created_at' => (string) $row['created_at']], $rows);
    }

    public function cancel(EntityId $id, int $actorId): void
    {
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE request_uuid = %s AND actor_id = %d', $this->table('import_jobs'), $id->value, $actorId));
    }
}
