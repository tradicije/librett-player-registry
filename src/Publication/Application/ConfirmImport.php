<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use DateTimeImmutable;
use LibreTT\PlayerRegistry\Publication\Domain\ImportJob;
use LibreTT\PlayerRegistry\Publication\Domain\ImportMapping;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final readonly class ConfirmImport
{
    public function __construct(
        private SnapshotValidator $validator,
        private ImportStore $store,
        private ImportTarget $target,
        private RegistryContextReader $registry,
        private Clock $clock,
        private UnitOfWork $transactions,
    ) {}

    /** @return array{players:int,clubs:int} */
    public function execute(Actor $actor, EntityId $request, string $payloadHash): array
    {
        $actor->assertCanImport();
        $result = ['players' => 0, 'clubs' => 0];
        $this->transactions->run(function () use ($actor, $request, $payloadHash, &$result): void {
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $receipt = $this->store->receipt($request);
            if ($receipt !== null) {
                if ($receipt['actor_id'] !== $actor->id || !hash_equals($receipt['payload_hash'], $payloadHash)) {
                    throw new RegistryFailure('request_reused');
                }
                $result = ['players' => $receipt['players'], 'clubs' => $receipt['clubs']];
                return;
            }
            $job = $this->store->job($request);
            if ($job === null || $job->actorId !== $actor->id || !hash_equals($job->payloadHash, $payloadHash)
                || !hash_equals($job->payloadHash, hash('sha256', $job->payload))) {
                throw new RegistryFailure('invalid_import_job');
            }
            $elapsed = (new DateTimeImmutable($this->clock->utcTimestamp()))->getTimestamp() - (new DateTimeImmutable($job->createdAt))->getTimestamp();
            if ($elapsed < 0 || $elapsed > 3600) {
                throw new RegistryFailure('preview_expired');
            }
            $snapshot = $this->validator->decode($job->payload);
            $repeat = $this->store->repeated($job->semanticHash);
            if ($repeat !== null) {
                // Semantic retries never overwrite intervening local edits.
                $result = $repeat;
            } else {
                $result = $this->target->apply($actor, $snapshot, $job->mappings);
            }
            $final = array_map(fn(ImportMapping $map): ImportMapping => new ImportMapping($map->type, $map->source, $map->local, $this->target->revision($map->type, $map->local) ?? 0), $job->mappings);
            $completed = new ImportJob($job->id, $job->actorId, $job->createdAt, $job->payload, $job->payloadHash, $job->semanticHash, $final);
            $this->store->complete($completed, new EntityId($snapshot->registry_id), $result['players'], $result['clubs']);
        });
        return $result;
    }
}
