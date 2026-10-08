<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use LibreTT\PlayerRegistry\Clubs\Application\ClubAliases;
use LibreTT\PlayerRegistry\Clubs\Application\ClubDraftRepository;
use LibreTT\PlayerRegistry\Clubs\Application\MembershipRepository;
use LibreTT\PlayerRegistry\Players\Application\PlayerDraftRepository;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryContextReader;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class ApproveProfile
{
    public const PLAYER_FIELDS = ['given_name', 'family_name', 'birth_year', 'country', 'region', 'biography', 'memberships', 'photo'];
    public const CLUB_FIELDS = ['abbreviation', 'aliases', 'country', 'region'];

    public function __construct(
        private PublicationStore $store,
        private PlayerDraftRepository $players,
        private ClubDraftRepository $clubs,
        private MembershipRepository $memberships,
        private ClubAliases $aliases,
        private RegistryContextReader $registry,
        private UnitOfWork $transactions,
        private ?\LibreTT\PlayerRegistry\Media\Application\PhotoCatalogue $photos = null,
    ) {}

    /** @param list<string> $fields */
    public function execute(Actor $actor, string $type, EntityId $id, int $expectedDraft, int $expectedPublic, string $policyVersion, array $fields, string $evidence, string $ageStatus): void
    {
        $actor->assertCanPublish();
        PlainText::assertValid($evidence, 1000, true);
        $allowed = match ($type) {
            'player' => self::PLAYER_FIELDS, 'club' => self::CLUB_FIELDS, default => throw new RegistryFailure('invalid_data')
        };
        if (count($fields) !== count(array_unique($fields)) || array_diff($fields, $allowed) !== []) {
            throw new RegistryFailure('invalid_data');
        }
        $this->transactions->run(function () use ($actor, $type, $id, $expectedDraft, $expectedPublic, $policyVersion, $fields, $evidence, $ageStatus): void {
            if ($this->registry->current() === null) {
                throw new RegistryFailure('registry_unconfigured');
            }
            $this->store->lockForMutation();
            $policy = $this->store->policy();
            if ($policy === null || $policy['policy']->version !== $policyVersion) {
                throw new RegistryFailure('policy_conflict');
            }
            $draft = $type === 'player' ? $this->players->find($id) : $this->clubs->find($id);
            if ($draft === null || $draft->revision->value !== $expectedDraft || $draft->state !== DraftState::Active) {
                throw new RegistryFailure('revision_conflict');
            }
            $data = $draft->data;
            $projection = ['id' => $id->value, 'slug' => $id->value];
            if ($data instanceof \LibreTT\PlayerRegistry\Players\Domain\PlayerData) {
                if (!in_array($ageStatus, ['adult', 'minor'], true) || ($ageStatus === 'minor' && $policy['policy']->minorPolicy === '')) {
                    throw new RegistryFailure('publication_review_required');
                }
                $projection['display_name'] = $data->name;
                $values = ['given_name' => $data->givenName, 'family_name' => $data->familyName, 'birth_year' => $data->birthYear,
                    'country' => $data->country, 'region' => $data->region, 'biography' => $data->biography];
                if (in_array('photo', $fields, true)) {
                    $photo = $this->photos?->forPlayer($id);
                    if ($photo === null) {
                        throw new RegistryFailure('unpublished_reference');
                    }
                    $this->store->approve('media', $photo->id, $this->store->revision('media', $photo->id), $this->photos->publicDescriptor($photo), $photo->rightsEvidence, $actor->id);
                    $projection['photo_id'] = $photo->id->value;
                }
                $projection['_clubs'] = [];
                if (in_array('memberships', $fields, true)) {
                    foreach ($this->memberships->forPlayer($id) as $club) {
                        if ($this->store->find('club', $club) === null) {
                            throw new RegistryFailure('unpublished_reference');
                        }
                        $projection['_clubs'][] = $club->value;
                    }
                }
            } else {
                $projection['name'] = $data->name;
                $values = ['abbreviation' => $data->abbreviation, 'aliases' => $this->aliases->forClub($id), 'country' => $data->country, 'region' => $data->region];
            }
            foreach ($values as $field => $value) {
                if (in_array($field, $fields, true) && $value !== null && $value !== '' && $value !== []) {
                    $projection[$field] = $value;
                }
            }
            $this->store->approve($type, $id, $expectedPublic, $projection, $evidence . "\nAge review: " . $ageStatus, $actor->id);
        });
    }
}
