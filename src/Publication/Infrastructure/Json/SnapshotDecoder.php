<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure\Json;

use JsonStreamingParser\Parser;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use Opis\JsonSchema\Validator;
use stdClass;
use Throwable;

/** @phpstan-import-type Snapshot from \LibreTT\PlayerRegistry\Publication\Application\SnapshotValidator */
final readonly class SnapshotDecoder implements \LibreTT\PlayerRegistry\Publication\Application\SnapshotValidator
{
    public const MAX_BYTES = 33554432;

    private stdClass $schema;

    public function __construct(stdClass $schema)
    {
        // Opis uses PCRE; JSON Schema patterns use ECMAScript Unicode escapes.
        $this->schema = clone $schema;
        $this->preparePatterns($this->schema);
    }

    private function preparePatterns(stdClass $node): void
    {
        foreach (get_object_vars($node) as $name => $value) {
            if ($name === 'pattern' && is_string($value)) {
                $node->$name = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static fn(array $match): string => mb_chr((int) hexdec($match[1]), 'UTF-8'), $value);
            } elseif ($value instanceof stdClass) {
                $copy = clone $value;
                $this->preparePatterns($copy);
                $node->$name = $copy;
            } elseif (is_array($value)) {
                $copies = [];
                foreach ($value as $key => $item) {
                    if ($item instanceof stdClass) {
                        $item = clone $item;
                        $this->preparePatterns($item);
                    }
                    $copies[$key] = $item;
                }
                $node->$name = $copies;
            }
        }
    }

    /** @return Snapshot */
    public function decode(string $json): stdClass
    {
        if (strlen($json) > self::MAX_BYTES || !mb_check_encoding($json, 'UTF-8')) {
            throw new RegistryFailure('resource_limit');
        }
        // Lexical resource guard only: the maintained parsers below validate JSON syntax.
        $inside = false;
        $escaped = false;
        $tokenBytes = 0;
        for ($index = 0, $length = strlen($json); $index < $length; ++$index) {
            $byte = $json[$index];
            if (!$inside) {
                if ($byte === '"') {
                    $inside = true;
                    $tokenBytes = 0;
                }
                continue;
            }
            if (!$escaped && $byte === '"') {
                $inside = false;
                continue;
            }
            if (++$tokenBytes > 32768) {
                throw new RegistryFailure('resource_limit');
            }
            $escaped = !$escaped && $byte === '\\';
        }
        $stream = fopen('php://temp/maxmemory:1048576', 'w+b');
        if ($stream === false) {
            throw new RegistryFailure('storage_failure');
        }
        try {
            if (fwrite($stream, $json) !== strlen($json) || !rewind($stream)) {
                throw new RegistryFailure('storage_failure');
            }
            $listener = new BoundedListener();
            (new Parser($stream, $listener))->parse();
            if (!$listener->finished) {
                throw new RegistryFailure('invalid_json');
            }
            $data = json_decode($json, false, 8, JSON_THROW_ON_ERROR);
            if (!$data instanceof stdClass || !(new Validator())->validate($data, $this->schema)->isValid()) {
                throw new RegistryFailure('invalid_snapshot');
            }
            /** @var Snapshot $data Validated by the bundled strict schema above. */
            $this->semantic($data);
            return $data;
        } catch (RegistryFailure $failure) {
            throw $failure;
        } catch (Throwable) {
            throw new RegistryFailure('invalid_snapshot');
        } finally {
            fclose($stream);
        }
    }

    /** @param Snapshot $data */
    private function semantic(stdClass $data): void
    {
        $timestamp = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $data->exported_at, new \DateTimeZone('UTC'));
        if ($timestamp === false || $timestamp->format('Y-m-d\TH:i:s\Z') !== $data->exported_at) {
            throw new RegistryFailure('invalid_snapshot');
        }
        new HttpsUrl($data->publication_policy->dataset_terms_url);
        new HttpsUrl($data->publication_policy->media_terms_url);
        $ids = [];
        foreach ([$data->players, $data->clubs, $data->media, $data->memberships, $data->tombstones] as $index => $records) {
            if (count($records) > [20000, 5000, 20000, 40000, 50000][$index]) {
                throw new RegistryFailure('resource_limit');
            }
        }
        foreach (['players' => $data->players, 'clubs' => $data->clubs, 'media' => $data->media] as $type => $records) {
            $ids[$type] = [];
            $slugs = [];
            foreach ($records as $record) {
                $slug = $record->slug ?? null;
                if ($slug !== null && !is_string($slug)) {
                    throw new RegistryFailure('invalid_snapshot');
                }
                if (isset($ids[$type][$record->id]) || (int) $record->revision > (int) $data->checkpoint
                    || ($slug !== null && isset($slugs[$slug]))) {
                    throw new RegistryFailure('invalid_snapshot');
                }
                $ids[$type][$record->id] = true;
                if ($slug !== null) {
                    $slugs[$slug] = true;
                }
            }
        }
        foreach ($data->media as $photo) {
            new HttpsUrl($photo->content_url);
            if ($photo->width * $photo->height > 16777216) {
                throw new RegistryFailure('resource_limit');
            }
        }
        $pairs = [];
        foreach ($data->memberships as $membership) {
            $key = $membership->player_id . ':' . $membership->club_id;
            if (isset($pairs[$key]) || !isset($ids['players'][$membership->player_id]) || !isset($ids['clubs'][$membership->club_id])) {
                throw new RegistryFailure('invalid_snapshot');
            }
            $pairs[$key] = true;
        }
        $referencedMedia = [];
        foreach ($data->players as $player) {
            if (isset($player->photo_id)) {
                if (!isset($ids['media'][$player->photo_id])) {
                    throw new RegistryFailure('invalid_snapshot');
                }
                $referencedMedia[$player->photo_id] = true;
            }
        }
        if (count($referencedMedia) !== count($ids['media'])) {
            throw new RegistryFailure('invalid_snapshot');
        }
        $tombstones = [];
        foreach ($data->tombstones as $tombstone) {
            $type = ['player' => 'players', 'club' => 'clubs', 'media' => 'media'][$tombstone->entity_type];
            $key = $type . ':' . $tombstone->entity_id;
            if (isset($tombstones[$key]) || isset($ids[$type][$tombstone->entity_id])
                || (int) $tombstone->removed_at_checkpoint > (int) $data->checkpoint
                || (int) $tombstone->revision > (int) $tombstone->removed_at_checkpoint) {
                throw new RegistryFailure('invalid_snapshot');
            }
            $tombstones[$key] = true;
        }
    }
}
