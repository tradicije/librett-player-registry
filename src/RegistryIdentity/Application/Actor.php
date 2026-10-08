<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\RegistryIdentity\Application;

final readonly class Actor
{
    /** @param list<string> $capabilities */
    public function __construct(public int $id, private array $capabilities) {}

    public function assertCanManageSettings(): void
    {
        if ($this->id < 1 || !in_array('librett_registry_manage_settings', $this->capabilities, true)) {
            throw new RegistryFailure('permission_denied');
        }
    }

    public function assertCanEditProfiles(): void
    {
        if ($this->id < 1 || !in_array('librett_registry_edit_profiles', $this->capabilities, true)) {
            throw new RegistryFailure('permission_denied');
        }
    }
    public function assertCanPublish(): void
    {
        if ($this->id < 1 || !in_array('librett_registry_publish_profiles', $this->capabilities, true)) {
            throw new RegistryFailure('permission_denied');
        }
    }
    public function assertCanImport(): void
    {
        $this->assertCanEditProfiles();
        if (!in_array('librett_registry_import', $this->capabilities, true)) {
            throw new RegistryFailure('permission_denied');
        }
    }
}
