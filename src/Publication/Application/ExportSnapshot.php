<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use stdClass;

/** @phpstan-import-type Snapshot from \LibreTT\PlayerRegistry\Publication\Application\SnapshotValidator */
final readonly class ExportSnapshot
{
    public function __construct(
        private PublicationStore $store,
        private RegistryContextReader $registry,
        private UnitOfWork $transactions,
        private Clock $clock,
        private SnapshotValidator $validator,
    ) {}

    /** @return Snapshot */
    public function execute(): stdClass
    {
        $json = '';
        $this->transactions->run(function () use (&$json): void {
            $registry = $this->registry->current();
            $policy = $this->store->policy();
            if ($registry === null || $policy === null) {
                throw new RegistryFailure('publication_unconfigured');
            }
            $snapshot = ['format' => 'librett-registry-public-snapshot', 'schema_version' => 1, 'registry_id' => $registry->id->value,
                'registry_name' => $registry->name, 'authority_generation' => null, 'exported_at' => $this->clock->utcTimestamp(),
                'publication_policy' => $policy['policy']->publicData()] + $this->store->snapshot();
            $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
        return $this->validator->decode($json);
    }
}
