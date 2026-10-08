<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Publication\Application\PublicationStore;
use LibreTT\PlayerRegistry\Publication\Domain\DatasetLicense;
use LibreTT\PlayerRegistry\Publication\Domain\Policy;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use Ramsey\Uuid\Uuid;

final readonly class WordPressPublicationStore implements PublicationStore
{
    public function __construct(private Database $db) {}

    private function table(string $name): string
    {
        return trim($this->db->table($name), '`');
    }

    public function policy(): ?array
    {
        $rows = $this->db->rows('SELECT edit_revision, payload FROM ' . $this->db->table('publication_policy') . ' WHERE singleton = 1');
        if ($rows === []) {
            return null;
        }
        $data = $this->decode((string) $rows[0]['payload']);
        return ['revision' => (int) $rows[0]['edit_revision'], 'policy' => new Policy($this->text($data, 'version'), $this->text($data, 'purpose'), DatasetLicense::from($this->text($data, 'license')), new HttpsUrl($this->text($data, 'dataset_terms_url')), new HttpsUrl($this->text($data, 'media_terms_url')), $this->text($data, 'minor_policy'), $this->text($data, 'document_key'))];
    }

    public function lockForMutation(): void
    {
        $this->lockHead();
    }

    private function lockHead(): int
    {
        $this->db->execute('INSERT IGNORE INTO ' . $this->db->table('publication_head') . ' (singleton, checkpoint) VALUES (1, 0)');
        return (int) $this->db->rows('SELECT checkpoint FROM ' . $this->db->table('publication_head') . ' WHERE singleton = 1 FOR UPDATE')[0]['checkpoint'];
    }

    private function nextCheckpoint(): int
    {
        $current = $this->lockHead();
        if ($current === PHP_INT_MAX) {
            throw new RegistryFailure('revision_exhausted');
        }
        $next = $current + 1;
        $this->db->execute($this->db->prepare('UPDATE %i SET checkpoint = %s WHERE singleton = 1', $this->table('publication_head'), (string) $next));
        return $next;
    }

    public function savePolicy(Policy $policy, int $expected, int $actorId): void
    {
        $this->lockHead();
        $current = $this->policy();
        if (($current['revision'] ?? 0) !== $expected || $expected === PHP_INT_MAX) {
            throw new RegistryFailure('revision_conflict');
        }
        $payload = $policy->publicData() + ['license' => $policy->license->value, 'minor_policy' => $policy->minorPolicy, 'document_key' => $policy->documentKey];
        if ($current !== null) {
            if ($current['policy'] == $policy) {
                return;
            }
            if ($current['policy']->version === $policy->version) {
                throw new RegistryFailure('policy_version_required');
            }
            // All previous approvals require review under a changed policy.
            foreach ($this->db->rows('SELECT entity_type, entity_uuid FROM ' . $this->db->table('public_records') . ' ORDER BY entity_type, entity_uuid') as $record) {
                $this->withdrawRecord((string) $record['entity_type'], new EntityId((string) $record['entity_uuid']), $actorId);
            }
        }
        $this->db->execute($this->db->prepare('INSERT INTO %i (singleton, edit_revision, payload) VALUES (1, %s, %s) ON DUPLICATE KEY UPDATE edit_revision = VALUES(edit_revision), payload = VALUES(payload)', $this->table('publication_policy'), (string) ($expected + 1), $this->encode($payload)));
        $checkpoint = $this->nextCheckpoint();
        $this->db->execute($this->db->prepare('INSERT INTO %i (checkpoint, entity_type, entity_uuid, revision, action) VALUES (%s, %s, %s, %s, %s)', $this->table('publication_events'), (string) $checkpoint, 'policy', '00000000-0000-4000-8000-000000000000', (string) ($expected + 1), 'metadata'));
        $this->audit('policy', new EntityId('00000000-0000-4000-8000-000000000000'), $actorId, 'policy_changed', '');
    }

    public function revision(string $type, EntityId $id): int
    {
        $rows = $this->db->rows($this->db->prepare('SELECT revision FROM %i WHERE entity_type = %s AND entity_uuid = %s', $this->table('public_ledger'), $type, $id->value));
        return $rows === [] ? 0 : (int) $rows[0]['revision'];
    }

    public function find(string $type, EntityId $id): ?array
    {
        $rows = $this->db->rows($this->db->prepare('SELECT payload FROM %i WHERE entity_type = %s AND entity_uuid = %s', $this->table('public_records'), $type, $id->value));
        return $rows === [] ? null : $this->decode((string) $rows[0]['payload']);
    }

    public function approve(string $type, EntityId $id, int $expectedPublic, array $projection, string $evidence, int $actorId): void
    {
        $this->lockHead();
        if ($this->revision($type, $id) !== $expectedPublic) {
            throw new RegistryFailure('revision_conflict');
        }
        $previous = $this->find($type, $id);
        if ($previous !== null) {
            unset($previous['revision']);
        }
        if ($previous === $projection) {
            $this->audit($type, $id, $actorId, 'approval_noop', $evidence);
            return;
        }
        $this->upsert($type, $id, $projection);
        $this->audit($type, $id, $actorId, 'approved', $evidence);
        if ($type === 'player') {
            $this->removeUnreferencedMedia($actorId);
        }
    }

    /** @param array<string, mixed> $projection */
    private function upsert(string $type, EntityId $id, array $projection): void
    {
        $revision = $this->revision($type, $id);
        if ($revision === PHP_INT_MAX) {
            throw new RegistryFailure('revision_exhausted');
        }
        $checkpoint = $this->nextCheckpoint();
        $projection['revision'] = (string) ($revision + 1);
        $this->db->execute($this->db->prepare('INSERT INTO %i (entity_type, entity_uuid, payload) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE payload = VALUES(payload)', $this->table('public_records'), $type, $id->value, $this->encode($projection)));
        $this->event($type, $id, $revision + 1, $checkpoint, 'live', 'upsert');
    }

    public function withdraw(string $type, EntityId $id, int $actorId): void
    {
        $this->lockHead();
        if ($this->find($type, $id) === null) {
            return;
        }
        if ($type === 'club' || $type === 'media') {
            foreach ($this->db->rows($this->db->prepare('SELECT entity_uuid, payload FROM %i WHERE entity_type = %s ORDER BY entity_uuid', $this->table('public_records'), 'player')) as $record) {
                $player = $this->decode((string) $record['payload']);
                $changed = false;
                if ($type === 'club' && in_array($id->value, $this->strings($player, '_clubs'), true)) {
                    $player['_clubs'] = array_values(array_filter($this->strings($player, '_clubs'), static fn(string $club): bool => $club !== $id->value));
                    $changed = true;
                }
                if ($type === 'media' && ($player['photo_id'] ?? null) === $id->value) {
                    unset($player['photo_id']);
                    $changed = true;
                }
                if ($changed) {
                    unset($player['revision']);
                    $this->upsert('player', new EntityId((string) $record['entity_uuid']), $player);
                }
            }
        }
        $this->withdrawRecord($type, $id, $actorId);
        if ($type !== 'media') {
            $this->removeUnreferencedMedia($actorId);
        }
    }

    private function withdrawRecord(string $type, EntityId $id, int $actorId): void
    {
        $revision = $this->revision($type, $id);
        if ($revision === PHP_INT_MAX) {
            throw new RegistryFailure('revision_exhausted');
        }
        $checkpoint = $this->nextCheckpoint();
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE entity_type = %s AND entity_uuid = %s', $this->table('public_records'), $type, $id->value));
        $this->event($type, $id, $revision + 1, $checkpoint, 'withdrawn', 'remove');
        $this->audit($type, $id, $actorId, 'withdrawn', '');
    }

    private function removeUnreferencedMedia(int $actorId): void
    {
        $references = [];
        foreach ($this->db->rows($this->db->prepare('SELECT payload FROM %i WHERE entity_type = %s', $this->table('public_records'), 'player')) as $row) {
            $data = $this->decode((string) $row['payload']);
            if (isset($data['photo_id'])) {
                $references[$this->text($data, 'photo_id')] = true;
            }
        }
        foreach ($this->db->rows($this->db->prepare('SELECT entity_uuid FROM %i WHERE entity_type = %s', $this->table('public_records'), 'media')) as $row) {
            $id = (string) $row['entity_uuid'];
            if (!isset($references[$id])) {
                $this->withdraw('media', new EntityId($id), $actorId);
            }
        }
    }

    private function event(string $type, EntityId $id, int $revision, int $checkpoint, string $state, string $action): void
    {
        $this->db->execute($this->db->prepare('INSERT INTO %i (entity_type, entity_uuid, revision, checkpoint, state) VALUES (%s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE revision = VALUES(revision), checkpoint = VALUES(checkpoint), state = VALUES(state)', $this->table('public_ledger'), $type, $id->value, (string) $revision, (string) $checkpoint, $state));
        $this->db->execute($this->db->prepare('INSERT INTO %i (checkpoint, entity_type, entity_uuid, revision, action) VALUES (%s, %s, %s, %s, %s)', $this->table('publication_events'), (string) $checkpoint, $type, $id->value, (string) $revision, $action));
    }

    private function audit(string $type, EntityId $id, int $actorId, string $action, string $evidence): void
    {
        $this->db->execute($this->db->prepare('INSERT INTO %i (audit_uuid, entity_type, entity_uuid, actor_id, policy_version, evidence, action) VALUES (%s, %s, %s, %d, %s, %s, %s)', $this->table('publication_audit'), Uuid::uuid4()->toString(), $type, $id->value, $actorId, $this->policy()['policy']->version ?? '', $evidence, $action));
    }

    public function snapshot(): array
    {
        $result = ['checkpoint' => '0', 'players' => [], 'clubs' => [], 'memberships' => [], 'media' => [], 'tombstones' => []];
        $head = $this->db->rows('SELECT checkpoint FROM ' . $this->db->table('publication_head') . ' WHERE singleton = 1');
        $result['checkpoint'] = (string) ($head[0]['checkpoint'] ?? '0');
        foreach ($this->db->rows('SELECT entity_type, payload FROM ' . $this->db->table('public_records') . ' ORDER BY entity_type, entity_uuid LIMIT 45001') as $row) {
            $data = $this->decode((string) $row['payload']);
            $type = (string) $row['entity_type'];
            if ($type === 'player') {
                foreach ($this->strings($data, '_clubs') as $club) {
                    $result['memberships'][] = ['player_id' => $this->text($data, 'id'), 'club_id' => (string) $club];
                }
                unset($data['_clubs']);
                $result['players'][] = $data;
            } elseif ($type === 'club') {
                $result['clubs'][] = $data;
            } elseif ($type === 'media') {
                $result['media'][] = $data;
            } else {
                throw new RegistryFailure('invalid_public_projection');
            }
        }
        foreach ($this->db->rows($this->db->prepare('SELECT entity_type, entity_uuid, revision, checkpoint FROM %i WHERE state = %s ORDER BY entity_type, entity_uuid LIMIT 50001', $this->table('public_ledger'), 'withdrawn')) as $row) {
            $result['tombstones'][] = ['entity_type' => (string) $row['entity_type'], 'entity_id' => (string) $row['entity_uuid'], 'revision' => (string) $row['revision'], 'removed_at_checkpoint' => (string) $row['checkpoint']];
        }
        if (count($result['players']) > 20000 || count($result['clubs']) > 5000 || count($result['media']) > 20000 || count($result['memberships']) > 40000 || count($result['tombstones']) > 50000) {
            throw new RegistryFailure('resource_limit');
        }
        return $result;
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        $data = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RegistryFailure('invalid_public_projection');
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new RegistryFailure('invalid_public_projection');
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /** @param array<string, mixed> $data */
    private function text(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value)) {
            throw new RegistryFailure('invalid_public_projection');
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function strings(array $data, string $key): array
    {
        $values = $data[$key] ?? [];
        if (!is_array($values) || !array_is_list($values)) {
            throw new RegistryFailure('invalid_public_projection');
        }
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new RegistryFailure('invalid_public_projection');
            }
        }
        return $values;
    }
}
