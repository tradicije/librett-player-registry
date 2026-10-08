<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Application;

use LibreTT\PlayerRegistry\Clubs\Application\ClubAliases;
use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftRepository;
use LibreTT\PlayerRegistry\Clubs\Application\MembershipRepository;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftRepository;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\Publication\Application\ImportTarget;
use LibreTT\PlayerRegistry\Publication\Domain\ImportMapping;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use stdClass;

/** @phpstan-import-type Snapshot from \LibreTT\PlayerRegistry\Publication\Application\SnapshotValidator */
final readonly class DraftImportTarget implements ImportTarget
{
    public function __construct(private PlayerDraftRepository $players, private ClubDraftRepository $clubs, private MembershipRepository $memberships, private ClubAliases $aliases) {}

    public function revision(string $type, EntityId $id): ?int
    {
        return match ($type) {
            'player' => $this->players->find($id)?->revision->value,
            'club' => $this->clubs->find($id)?->revision->value, default => throw new RegistryFailure('invalid_mapping')
        };
    }

    /**
     * @param Snapshot $snapshot
     * @param list<ImportMapping> $mappings
     * @return array{players:int,clubs:int}
     */
    public function apply(Actor $actor, stdClass $snapshot, array $mappings): array
    {
        $actor->assertCanImport();
        $maps = [];
        // Lock all targets in deterministic order before any mutations.
        $ordered = $mappings;
        usort($ordered, static fn(ImportMapping $a, ImportMapping $b): int => strcmp($a->type . $a->local->value, $b->type . $b->local->value));
        foreach ($ordered as $map) {
            if (($this->revision($map->type, $map->local) ?? 0) !== $map->expected) {
                throw new RegistryFailure('preview_stale');
            }
            $maps[$map->type . ':' . $map->source->value] = $map;
        }
        $result = ['players' => 0, 'clubs' => 0];
        foreach ($snapshot->clubs as $source) {
            $map = $maps['club:' . $source->id] ?? throw new RegistryFailure('invalid_mapping');
            $current = $this->clubs->find($map->local);
            $data = new ClubData(
                $source->name,
                $source->country ?? $current?->data->country ?? '',
                $source->region ?? $current?->data->region ?? '',
                $source->abbreviation ?? $current?->data->abbreviation ?? '',
            );
            $aliases = $source->aliases ?? ($current === null ? [] : $this->aliases->forClub($map->local));
            $existingAliases = $current === null ? [] : $this->aliases->forClub($map->local);
            sort($aliases, SORT_STRING);
            sort($existingAliases, SORT_STRING);
            if ($current === null || $current->data != $data || $aliases !== $existingAliases) {
                $expected = new EditRevision($map->expected);
                $this->clubs->save(new ClubDraft($map->local, $expected->next(), $data, $current->state ?? DraftState::Active), $expected, $actor->id);
                $this->aliases->replace($map->local, $aliases);
                ++$result['clubs'];
            }
        }
        $links = [];
        foreach ($snapshot->memberships as $link) {
            $clubMap = $maps['club:' . $link->club_id] ?? throw new RegistryFailure('invalid_mapping');
            $links[$link->player_id][] = $clubMap->local;
        }
        foreach ($snapshot->players as $source) {
            $map = $maps['player:' . $source->id] ?? throw new RegistryFailure('invalid_mapping');
            $current = $this->players->find($map->local);
            $data = new PlayerData(
                $source->display_name,
                $source->country ?? $current?->data->country ?? '',
                $source->region ?? $current?->data->region ?? '',
                $source->given_name ?? $current?->data->givenName ?? '',
                $source->family_name ?? $current?->data->familyName ?? '',
                $source->biography ?? $current?->data->biography ?? '',
                $source->birth_year ?? $current?->data->birthYear,
            );
            $clubs = $links[$source->id] ?? [];
            usort($clubs, static fn(EntityId $a, EntityId $b): int => strcmp($a->value, $b->value));
            $existing = $current === null ? [] : $this->memberships->forPlayer($map->local);
            if ($current?->state === DraftState::Archived && $clubs !== []) {
                throw new RegistryFailure('archived_mapping_requires_review');
            }
            if ($current === null || $current->data != $data || $clubs != $existing) {
                $expected = new EditRevision($map->expected);
                $this->players->save(new PlayerDraft($map->local, $expected->next(), $data, $current->state ?? DraftState::Active), $expected, $actor->id);
                $this->memberships->replace($map->local, $clubs, $actor->id);
                ++$result['players'];
            }
        }
        return $result;
    }
}
