<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Clubs\Infrastructure;

use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftReader;
use LibreTT\PlayerRegistry\Clubs\Application\SaveClubDraft;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubDraft;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftPage;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftView;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Preflight;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;

final class ClubPage
{
    public static function register(ClubDraftReader $reader, SaveClubDraft $save, DraftSchema $schema, Preflight $preflight): void
    {
        (new DraftPage(
            'librett-registry-clubs',
            __('Clubs', 'librett-player-registry'),
            [
                'name' => ['label' => __('Club name', 'librett-player-registry'), 'limit' => 200, 'type' => 'text'],
                'country' => ['label' => __('Country', 'librett-player-registry'), 'limit' => 100, 'type' => 'text'],
                'region' => ['label' => __('Region', 'librett-player-registry'), 'limit' => 100, 'type' => 'text'],
                'abbreviation' => ['label' => __('Abbreviation', 'librett-player-registry'), 'limit' => 32, 'type' => 'text'],
            ],
            static fn (Actor $actor, string $query, int $offset): array => array_map(self::view(...), $reader->search($actor, $query, $offset)),
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
                    new ClubData(
                        name: $values['name'],
                        country: $values['country'],
                        region: $values['region'],
                        abbreviation: $values['abbreviation'],
                    ),
                    $state,
                )->id;
            },
            $schema,
            $preflight,
        ))->register();
    }

    private static function view(ClubDraft $draft): DraftView
    {
        return new DraftView($draft->id, $draft->revision, $draft->state, [
            'name' => $draft->data->name,
            'country' => $draft->data->country,
            'region' => $draft->data->region,
            'abbreviation' => $draft->data->abbreviation,
        ]);
    }
}
