<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Application\DraftImportTarget;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\RamseyEntityIdGenerator;
use LibreTT\PlayerRegistry\Publication\Application\ConfirmImport;
use LibreTT\PlayerRegistry\Publication\Application\StageImport;
use LibreTT\PlayerRegistry\Publication\Infrastructure\SystemClock;
use LibreTT\PlayerRegistry\Publication\Infrastructure\WordPressImportStore;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;

final class SnapshotImportTest extends CatalogueTestCase
{
    private function workflow(): array
    {
        $store = new WordPressImportStore($this->db);
        $target = new DraftImportTarget($this->players, $this->clubs, $this->memberships, $this->aliases);
        $clock = new SystemClock();
        return [new StageImport($this->decoder, $store, $target, $this->registry, new RamseyEntityIdGenerator(), $clock, $this->db),
            new ConfirmImport($this->decoder, $store, $target, $this->registry, $clock, $this->db), $store];
    }

    private function payload(): string
    {
        return file_get_contents(__DIR__ . '/../../docs/contracts/examples/published.json');
    }

    public function testImportCreatesFreshPrivateIDsAndRepeatedConfirmationIsIdempotent(): void
    {
        [$stage, $confirm, $store] = $this->workflow();
        $registry = $this->registry->current()->id->value;
        $job = $stage->execute($this->actor(), $this->payload());
        self::assertSame([], $this->players->search('', 0));
        $result = $confirm->execute($this->actor(), $job->id, $job->payloadHash);
        self::assertSame(['players' => 2, 'clubs' => 1], $result);
        self::assertSame($result, $confirm->execute($this->actor(), $job->id, $job->payloadHash));
        self::assertCount(2, $this->players->search('', 0));
        self::assertSame($registry, $this->registry->current()->id->value);
        self::assertSame([], $this->store->snapshot()['players']);
        foreach ($job->mappings as $mapping) {
            self::assertNotSame($mapping->source->value, $mapping->local->value);
        }
        self::assertCount(2, $this->db->rows('SELECT * FROM ' . $this->db->table('players_audit')));
        $again = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $again->id, $again->payloadHash);
        self::assertCount(2, $this->db->rows('SELECT * FROM ' . $this->db->table('players_audit')));
        self::assertSame([], $store->pending($this->actor()->id));
    }

    public function testSecondPreviewCannotReplaceMappingsCreatedByFirstConfirmation(): void
    {
        [$stage, $confirm, $store] = $this->workflow();
        $first = $stage->execute($this->actor(), $this->payload());
        $second = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $first->id, $first->payloadHash);
        try {
            $confirm->execute($this->actor(), $second->id, $second->payloadHash);
            self::fail('Preview with obsolete mappings applied.');
        } catch (RegistryFailure $failure) {
            self::assertSame('preview_stale', $failure->errorCode);
        }
        self::assertCount(2, $this->players->search('', 0));
        self::assertCount(1, $this->clubs->search('', 0));
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('import_receipts')));
        $registry = new EntityId(json_decode($this->payload())->registry_id);
        foreach ($first->mappings as $map) {
            self::assertSame($map->local->value, $store->mapping($registry, $map->type, $map->source)['local']->value);
        }
        self::assertNotNull($store->job($second->id));
    }

    public function testExplicitRemappingInvalidatesAnEarlierPreview(): void
    {
        [$stage, $confirm, $store] = $this->workflow();
        $first = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $first->id, $first->payloadHash);
        $earlier = $stage->execute($this->actor(), $this->payload());
        $players = array_values(array_filter($first->mappings, static fn($map): bool => $map->type === 'player'));
        $overrides = [
            'player:' . $players[0]->source->value => $players[1]->local,
            'player:' . $players[1]->source->value => $players[0]->local,
        ];
        $remap = $stage->execute($this->actor(), $this->payload(), $overrides);
        $confirm->execute($this->actor(), $remap->id, $remap->payloadHash);
        try {
            $confirm->execute($this->actor(), $earlier->id, $earlier->payloadHash);
            self::fail('Earlier preview restored obsolete mappings.');
        } catch (RegistryFailure $failure) {
            self::assertSame('preview_stale', $failure->errorCode);
        }
        $registry = new EntityId(json_decode($this->payload())->registry_id);
        foreach ($players as $map) {
            self::assertSame($overrides['player:' . $map->source->value]->value, $store->mapping($registry, 'player', $map->source)['local']->value);
        }
        self::assertCount(2, $this->players->search('', 0));
    }

    public function testChangedPayloadUnderSameRequestIsRejected(): void
    {
        [$stage, $confirm] = $this->workflow();
        $job = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $job->id, $job->payloadHash);
        $this->expectException(RegistryFailure::class);
        $confirm->execute($this->actor(), $job->id, hash('sha256', 'other payload'));
    }

    public function testStalePreviewRejectsAllChangesAndRetainsLocalDraft(): void
    {
        [$stage, $confirm] = $this->workflow();
        $job = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $job->id, $job->payloadHash);
        $data = json_decode($this->payload());
        $data->players[0]->display_name = 'Changed source name';
        $newJob = $stage->execute($this->actor(), json_encode($data));
        $map = array_values(array_filter($newJob->mappings, static fn($map): bool => $map->type === 'player'))[0];
        $draft = $this->players->find($map->local);
        $this->savePlayer()->execute($this->actor(), $map->local, $draft->revision, new PlayerData('New local name'), DraftState::Active);
        try {
            $confirm->execute($this->actor(), $newJob->id, $newJob->payloadHash);
            self::fail('Stale preview applied.');
        } catch (RegistryFailure $failure) {
            self::assertSame('preview_stale', $failure->errorCode);
        }
        self::assertSame('New local name', $this->players->find($map->local)->data->name);
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('import_receipts')));
    }

    public function testSemanticRepeatPreservesLocalEditsAndOptionalOmissionRetainsValues(): void
    {
        [$stage, $confirm] = $this->workflow();
        $job = $stage->execute($this->actor(), $this->payload());
        $confirm->execute($this->actor(), $job->id, $job->payloadHash);
        $map = array_values(array_filter($job->mappings, static fn($map): bool => $map->type === 'player'))[0];
        $draft = $this->players->find($map->local);
        $this->savePlayer()->execute($this->actor(), $map->local, $draft->revision, new PlayerData('Local override', country: 'Local country', birthYear: 1990), DraftState::Active);
        $data = json_decode($this->payload());
        $data->exported_at = '2026-10-08T00:00:00Z';
        $repeat = $stage->execute($this->actor(), json_encode($data));
        $confirm->execute($this->actor(), $repeat->id, $repeat->payloadHash);
        self::assertSame('Local override', $this->players->find($map->local)->data->name);
        $data->players[0]->display_name = 'Reviewed source update';
        unset($data->players[0]->birth_year);
        $changed = $stage->execute($this->actor(), json_encode($data));
        $confirm->execute($this->actor(), $changed->id, $changed->payloadHash);
        self::assertSame('Local country', $this->players->find($map->local)->data->country);
        self::assertSame(1990, $this->players->find($map->local)->data->birthYear);
    }

    public function testPermissionAndActorOwnershipAreRecheckedAtConfirmation(): void
    {
        [$stage, $confirm] = $this->workflow();
        $job = $stage->execute($this->actor(), $this->payload());
        try {
            $confirm->execute(new Actor(2, ['librett_registry_edit_profiles','librett_registry_import']), $job->id, $job->payloadHash);
            self::fail('Other actor applied preview.');
        } catch (RegistryFailure $failure) {
            self::assertSame('invalid_import_job', $failure->errorCode);
        }
        self::assertSame([], $this->players->search('', 0));
        $this->expectException(RegistryFailure::class);
        $confirm->execute(new Actor(1, []), $job->id, $job->payloadHash);
    }
}
