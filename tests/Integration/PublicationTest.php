<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Application\ChangeMemberships;
use LibreTT\PlayerRegistry\Application\ChangePhoto;
use LibreTT\PlayerRegistry\Clubs\Application\SetMemberships;
use LibreTT\PlayerRegistry\Clubs\Domain\ClubData;
use LibreTT\PlayerRegistry\Media\Infrastructure\GdPhotoProcessor;
use LibreTT\PlayerRegistry\Media\Infrastructure\MediaDelivery;
use LibreTT\PlayerRegistry\Players\Application\TouchPlayerDraftRevision;
use LibreTT\PlayerRegistry\Players\Domain\PlayerData;
use LibreTT\PlayerRegistry\Publication\Application\WithdrawProfile;
use LibreTT\PlayerRegistry\Publication\Infrastructure\LicenseDocumentUpload;
use LibreTT\PlayerRegistry\Publication\Infrastructure\PublicApi;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\DraftState;
use LibreTT\PlayerRegistry\Shared\Domain\EditRevision;
use WP_Error;
use WP_REST_Request;

final class PublicationTest extends CatalogueTestCase
{
    public function testSyntheticExportForDesktopRoundTrip(): void
    {
        $this->configurePolicy();
        $club = $this->saveClub()->execute($this->actor(), null, new EditRevision(0), new ClubData('Roundtrip club'), DraftState::Active);
        $this->approve->execute($this->actor(), 'club', $club->id, 1, 0, 'synthetic-1', [], 'PRIVATE_ROUNDTRIP_EVIDENCE', 'not-applicable');
        foreach ([1990, null] as $year) {
            $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Roundtrip player', birthYear: $year, biography: 'PRIVATE_ROUNDTRIP_TEXT'), DraftState::Active);
            (new ChangeMemberships($this->db, new TouchPlayerDraftRevision($this->players), new SetMemberships($this->memberships, $this->players, $this->registry)))->execute($this->actor(), $player->id, $player->revision, [$club->id]);
            $this->approve->execute($this->actor(), 'player', $player->id, 2, 0, 'synthetic-1', ['birth_year', 'memberships'], 'PRIVATE_ROUNDTRIP_EVIDENCE', 'adult');
        }
        $bytes = json_encode($this->export->execute(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::assertStringNotContainsString('PRIVATE_ROUNDTRIP', $bytes);
        $directory = dirname(__DIR__, 2) . '/local/dev';
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0755, true));
        }
        self::assertSame(strlen($bytes), file_put_contents($directory . '/roundtrip-snapshot.json', $bytes));
    }

    public function testPrivateEditsNeverLeakAndNoopApprovalDoesNotAdvanceCheckpoint(): void
    {
        $this->configurePolicy();
        $draft = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Synthetic name', biography: 'Private text'), DraftState::Active);
        self::assertSame([], $this->export->execute()->players);
        $this->approve->execute($this->actor(), 'player', $draft->id, 1, 0, 'synthetic-1', [], 'Synthetic permission reference', 'adult');
        $first = $this->export->execute();
        self::assertSame('Synthetic name', $first->players[0]->display_name);
        self::assertObjectNotHasProperty('biography', $first->players[0]);
        $draft = $this->savePlayer()->execute($this->actor(), $draft->id, $draft->revision, new PlayerData('Unapproved changed name'), DraftState::Active);
        self::assertSame('Synthetic name', $this->export->execute()->players[0]->display_name);
        $this->approve->execute($this->actor(), 'player', $draft->id, 2, 1, 'synthetic-1', [], 'New permission', 'adult');
        $updated = $this->export->execute();
        self::assertSame('Unapproved changed name', $updated->players[0]->display_name);
        $this->approve->execute($this->actor(), 'player', $draft->id, 2, 2, 'synthetic-1', [], 'Repeated permission', 'adult');
        self::assertSame($updated->checkpoint, $this->export->execute()->checkpoint);
        self::assertStringNotContainsString('permission', json_encode($this->export->execute()));
    }

    public function testUnknownMinorAndEditorOnlyApprovalAreRejected(): void
    {
        $this->configurePolicy();
        $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Synthetic child'), DraftState::Active);
        foreach (['unknown', 'minor'] as $status) {
            try {
                $this->approve->execute($this->actor(), 'player', $player->id, 1, 0, 'synthetic-1', [], 'Synthetic evidence', $status);
                self::fail('Unreviewed age accepted.');
            } catch (RegistryFailure $failure) {
                self::assertSame('publication_review_required', $failure->errorCode);
            }
        }
        try {
            $this->approve->execute(new Actor(1, ['librett_registry_edit_profiles']), 'player', $player->id, 1, 0, 'synthetic-1', [], 'Synthetic evidence', 'adult');
            self::fail('Editor published.');
        } catch (RegistryFailure $failure) {
            self::assertSame('permission_denied', $failure->errorCode);
        }
        self::assertSame([], $this->export->execute()->players);
    }

    public function testClubWithdrawalRemovesPublicLinksAndAdvancesPlayerAtomically(): void
    {
        $this->configurePolicy();
        $club = $this->saveClub()->execute($this->actor(), null, new EditRevision(0), new ClubData('Synthetic club'), DraftState::Active);
        $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Synthetic player'), DraftState::Active);
        (new ChangeMemberships($this->db, new TouchPlayerDraftRevision($this->players), new SetMemberships($this->memberships, $this->players, $this->registry)))->execute($this->actor(), $player->id, $player->revision, [$club->id]);
        $this->approve->execute($this->actor(), 'club', $club->id, 1, 0, 'synthetic-1', [], 'Synthetic evidence', 'not-applicable');
        $this->approve->execute($this->actor(), 'player', $player->id, 2, 0, 'synthetic-1', ['memberships'], 'Synthetic evidence', 'adult');
        self::assertCount(1, $this->export->execute()->memberships);
        $this->saveClub()->execute($this->actor(), $club->id, $club->revision, $club->data, DraftState::Archived);
        $snapshot = $this->export->execute();
        self::assertSame([], $snapshot->memberships);
        self::assertSame('2', $snapshot->players[0]->revision);
        self::assertSame('club', $snapshot->tombstones[0]->entity_type);
        self::assertSame([], $this->memberships->forPlayer($player->id));
    }

    public function testPolicyChangeWithdrawsWithoutAutomaticRepublication(): void
    {
        $this->configurePolicy();
        $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Synthetic player'), DraftState::Active);
        $this->approve->execute($this->actor(), 'player', $player->id, 1, 0, 'synthetic-1', [], 'Synthetic evidence', 'adult');
        $this->configurePolicy('synthetic-2', 1);
        $snapshot = $this->export->execute();
        self::assertSame([], $snapshot->players);
        self::assertSame('2', $snapshot->tombstones[0]->revision);
        $this->approve->execute($this->actor(), 'player', $player->id, 1, 2, 'synthetic-2', [], 'New policy approval', 'adult');
        self::assertSame('3', $this->export->execute()->players[0]->revision);
        self::assertSame([], $this->export->execute()->tombstones);
    }

    public function testPhotoDerivativeIsPrivateUntilApprovedAndWithdrawnDeliveryIsClosed(): void
    {
        $this->configurePolicy();
        $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Synthetic photo player'), DraftState::Active);
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        (new ChangePhoto(new GdPhotoProcessor($this->files), $this->photos, new TouchPlayerDraftRevision($this->players), $this->players, $this->registry, $this->db))->execute($this->actor(), $player->id, $player->revision, $bytes . 'private trailing text', 'Synthetic photographer', 'Synthetic photo rights');
        $photo = $this->photos->forPlayer($player->id);
        self::assertStringNotContainsString('private trailing text', $this->files->read($photo->key, 5242880));
        $delivery = new MediaDelivery($this->photos, $this->files, $this->store, $this->schema);
        $request = new WP_REST_Request('GET');
        $request->set_param('id', $photo->id->value);
        self::assertInstanceOf(WP_Error::class, $delivery->publicPhoto($request));
        $this->approve->execute($this->actor(), 'player', $player->id, 2, 0, 'synthetic-1', ['photo'], 'Synthetic profile rights', 'adult');
        self::assertCount(1, $this->export->execute()->media);
        self::assertSame($photo->length, strlen($delivery->publicPhoto($request)->bytes));
        (new WithdrawProfile($this->store, $this->db))->execute($this->actor(), 'player', $player->id, 1);
        self::assertInstanceOf(WP_Error::class, $delivery->publicPhoto($request));
        self::assertSame([], $this->export->execute()->media);
        self::assertCount(2, $this->export->execute()->tombstones);
    }

    public function testLicenseUploadIsBoundedAndRejectsHTML(): void
    {
        $upload = new LicenseDocumentUpload($this->files);
        $key = $upload->store('Synthetic custom license terms.');
        self::assertStringEndsWith('.txt', $key);
        self::assertSame('Synthetic custom license terms.', $this->files->read($key, 1048576));
        $this->expectException(RegistryFailure::class);
        $upload->store('<html><script>alert(1)</script></html>');
    }

    public function testPublicAPIDoesNotExposeDraftLookup(): void
    {
        $this->configurePolicy();
        $player = $this->savePlayer()->execute($this->actor(), null, new EditRevision(0), new PlayerData('Unpublished synthetic'), DraftState::Active);
        $api = new PublicApi($this->export, $this->schema);
        $request = new WP_REST_Request('GET', '/librett-registry/v1/players/' . $player->id->value);
        $request->set_param('id', $player->id->value);
        $response = $api->handle($request);
        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame(404, $response->get_error_data()['status']);
    }
}
