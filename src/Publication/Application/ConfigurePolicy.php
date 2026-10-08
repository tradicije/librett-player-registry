<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Publication\Domain\Policy;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;

final readonly class ConfigurePolicy
{
    public function __construct(private PublicationStore $store, private RegistryContextReader $registry, private UnitOfWork $transactions) {}

    public function execute(Actor $actor, Policy $policy, int $expected): void
    {
        $actor->assertCanManageSettings();
        $this->transactions->run(function () use ($actor, $policy, $expected): void {
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $this->store->savePolicy($policy, $expected, $actor->id);
        });
    }
}
