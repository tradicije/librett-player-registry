<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Application;

use LibreTT\PlayerRegistry\RegistryIdentity\Domain\Registry;

final readonly class CreateRegistry
{
    public function __construct(
        private RegistryRepository $repository,
        private UnitOfWork $transactions,
        private UuidGenerator $ids,
    ) {}

    public function execute(Actor $actor, string $name): Registry
    {
        $actor->assertCanManageSettings();
        $registry = new Registry($this->ids->generate(), $name);
        $this->transactions->run(function () use ($registry, $actor): void {
            $this->repository->createIfUnconfigured($registry, $actor->id);
        });
        return $registry;
    }
}
