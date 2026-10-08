<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Players\Infrastructure;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftPage;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SchemaGate;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftView;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Preflight;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftReader;
use LibreTT\PlayerRegistry\Players\Application\SavePlayerDraft;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Players\Domain\PlayerDraft;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final class PlayerPage
{
    public static function register(PlayerDraftReader $reader, SavePlayerDraft $save, SchemaGate $schema, Preflight $preflight): void
    {
        (new DraftPage(
            'librett-registry-players',
            __('Players', 'librett-player-registry'),
            [
                'name' => ['label' => __('Display name', 'librett-player-registry'), 'limit' => 200, 'type' => 'text'],
                'country' => ['label' => __('Country', 'librett-player-registry'), 'limit' => 100, 'type' => 'text'],
                'region' => ['label' => __('Region', 'librett-player-registry'), 'limit' => 100, 'type' => 'text'],
                'givenName' => ['label' => __('Given name', 'librett-player-registry'), 'limit' => 200, 'type' => 'text'],
                'familyName' => ['label' => __('Family name', 'librett-player-registry'), 'limit' => 200, 'type' => 'text'],
                'biography' => ['label' => __('Biography', 'librett-player-registry'), 'limit' => 5000, 'type' => 'textarea'],
                'birthYear' => ['label' => __('Birth year (optional)', 'librett-player-registry'), 'limit' => 4, 'type' => 'text'],
            ],
            static fn(Actor $actor, string $query, int $offset): array => array_map(self::view(...), $reader->search($actor, $query, $offset)),
            static function (Actor $actor, EntityId $id) use ($reader): ?DraftView {
                $draft = $reader->find($actor, $id);
                return $draft === null ? null : self::view($draft);
            },
            /** @param array<string, string> $values */
            static function (Actor $actor, ?EntityId $id, EditRevision $revision, array $values, DraftState $state) use ($save): EntityId {
                return $save->execute(
                    $actor,
                    $id,
                    $revision,
                    new PlayerData(
                        name: $values['name'],
                        country: $values['country'],
                        region: $values['region'],
                        givenName: $values['givenName'],
                        familyName: $values['familyName'],
                        biography: $values['biography'],
                        birthYear: $values['birthYear'] === '' ? null : DraftRequest::counter($values['birthYear'], 9999),
                    ),
                    $state,
                )->id;
            },
            $schema,
            $preflight,
        ))->register();
    }

    private static function view(PlayerDraft $draft): DraftView
    {
        return new DraftView($draft->id, $draft->revision, $draft->state, [
            'name' => $draft->data->name,
            'country' => $draft->data->country,
            'region' => $draft->data->region,
            'givenName' => $draft->data->givenName,
            'familyName' => $draft->data->familyName,
            'biography' => $draft->data->biography,
            'birthYear' => $draft->data->birthYear === null ? '' : (string) $draft->data->birthYear,
        ]);
    }
}
