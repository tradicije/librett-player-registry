<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Publication\Domain\ImportJob;
use LibreTT\PlayerRegistry\Publication\Domain\ImportMapping;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Application\EntityIdGenerator;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use stdClass;

final readonly class StageImport
{
    public function __construct(
        private SnapshotValidator $validator,
        private ImportStore $store,
        private ImportTarget $target,
        private RegistryContextReader $registry,
        private EntityIdGenerator $ids,
        private Clock $clock,
        private UnitOfWork $transactions,
    ) {}

    /** @param array<string, EntityId> $overrides Keys are type:source-uuid; no name inference. */
    public function execute(Actor $actor, string $payload, array $overrides = []): ImportJob
    {
        $actor->assertCanImport();
        $snapshot = $this->validator->decode($payload);
        if ($this->registry->current() === null) {
            throw new RegistryFailure('registry_unconfigured');
        }
        $sourceRegistry = new EntityId($snapshot->registry_id);
        $mappings = [];
        $seen = [];
        $sourceKeys = [];
        foreach (['club' => $snapshot->clubs, 'player' => $snapshot->players] as $type => $records) {
            foreach ($records as $record) {
                $source = new EntityId($record->id);
                $key = $type . ':' . $source->value;
                $sourceKeys[$key] = true;
                $previous = $this->store->mapping($sourceRegistry, $type, $source);
                $local = $overrides[$key] ?? $previous['local'] ?? $this->ids->generate();
                $revision = $this->target->revision($type, $local);
                if (isset($overrides[$key]) && $revision === null || isset($seen[$type . ':' . $local->value])) {
                    throw new RegistryFailure('invalid_mapping');
                }
                $seen[$type . ':' . $local->value] = true;
                $mappings[] = new ImportMapping($type, $source, $local, $revision ?? 0);
            }
        }
        if (array_diff_key($overrides, $sourceKeys) !== []) {
            throw new RegistryFailure('invalid_mapping');
        }
        $semantic = clone $snapshot;
        unset($semantic->exported_at);
        $normalized = $this->normalize($semantic);
        $mapKeys = array_map(static fn(ImportMapping $map): string => $map->type . ':' . $map->source->value . ':' . $map->local->value, $mappings);
        sort($mapKeys, SORT_STRING);
        $job = new ImportJob(
            $this->ids->generate(),
            $actor->id,
            $this->clock->utcTimestamp(),
            $payload,
            hash('sha256', $payload),
            hash('sha256', json_encode([$normalized, $mapKeys], JSON_THROW_ON_ERROR)),
            $mappings,
        );
        $this->transactions->run(fn() => $this->store->stage($job));
        return $job;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $fields = get_object_vars($value);
            ksort($fields, SORT_STRING);
            $result = new stdClass();
            foreach ($fields as $key => $field) {
                $result->$key = $this->normalize($field);
            }
            return $result;
        }
        if (is_array($value)) {
            $items = array_map($this->normalize(...), $value);
            usort($items, static fn(mixed $left, mixed $right): int => strcmp(json_encode($left, JSON_THROW_ON_ERROR), json_encode($right, JSON_THROW_ON_ERROR)));
            return $items;
        }
        return $value;
    }
}
