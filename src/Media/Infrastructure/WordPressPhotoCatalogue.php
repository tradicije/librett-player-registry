<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Media\Application\PhotoCatalogue;
use LibreTT\PlayerRegistry\Media\Domain\PhotoAsset;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use Ramsey\Uuid\Uuid;

final readonly class WordPressPhotoCatalogue implements PhotoCatalogue
{
    public function __construct(private Database $db, private string $publicBaseUrl) {}

    public function forPlayer(EntityId $player): ?PhotoAsset
    {
        $rows = $this->db->rows($this->db->prepare('SELECT media_uuid FROM %i WHERE player_uuid = %s', trim($this->db->table('player_photos'), '`'), $player->value));
        return $rows === [] ? null : $this->find(new EntityId((string) $rows[0]['media_uuid']));
    }

    public function find(EntityId $id): ?PhotoAsset
    {
        $rows = $this->db->rows($this->db->prepare('SELECT * FROM %i WHERE entity_uuid = %s', trim($this->db->table('media_assets'), '`'), $id->value));
        if ($rows === []) {
            return null;
        }
        $r = $rows[0];
        return new PhotoAsset($id, (string) $r['storage_key'], (string) $r['digest'], (string) $r['mime'], (int) $r['byte_length'], (int) $r['width'], (int) $r['height'], (string) $r['attribution'], (string) $r['rights_evidence']);
    }

    public function attach(EntityId $player, ?PhotoAsset $photo, int $actorId): void
    {
        if ($photo !== null) {
            $this->db->execute($this->db->prepare('INSERT INTO %i (entity_uuid, storage_key, digest, mime, byte_length, width, height, attribution, rights_evidence) VALUES (%s, %s, %s, %s, %d, %d, %d, %s, %s)', trim($this->db->table('media_assets'), '`'), $photo->id->value, $photo->key, $photo->digest, $photo->mime, $photo->length, $photo->width, $photo->height, $photo->attribution, $photo->rightsEvidence));
        }
        $this->db->execute($this->db->prepare('DELETE FROM %i WHERE player_uuid = %s', trim($this->db->table('player_photos'), '`'), $player->value));
        if ($photo !== null) {
            $this->db->execute($this->db->prepare('INSERT INTO %i (player_uuid, media_uuid) VALUES (%s, %s)', trim($this->db->table('player_photos'), '`'), $player->value, $photo->id->value));
        }
        $this->db->execute($this->db->prepare('INSERT INTO %i (audit_uuid, player_uuid, actor_id, action) VALUES (%s, %s, %d, %s)', trim($this->db->table('media_audit'), '`'), Uuid::uuid4()->toString(), $player->value, $actorId, $photo === null ? 'photo_removed' : 'photo_attached'));
    }

    public function publicDescriptor(PhotoAsset $photo): array
    {
        $url = new HttpsUrl(rtrim($this->publicBaseUrl, '/') . '/' . $photo->id->value);
        return ['id' => $photo->id->value, 'content_sha256' => $photo->digest, 'mime_type' => $photo->mime, 'byte_length' => $photo->length, 'width' => $photo->width, 'height' => $photo->height, 'content_url' => $url->value, 'attribution' => $photo->attribution];
    }
}
