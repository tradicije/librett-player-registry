<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdminGuard;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\DraftRequest;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\UploadRequest;
use LibreTT\PlayerRegistry\Publication\Application\ConfigurePolicy;
use LibreTT\PlayerRegistry\Publication\Application\PublicationStore;
use LibreTT\PlayerRegistry\Publication\Domain\DatasetLicense;
use LibreTT\PlayerRegistry\Publication\Domain\Policy;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use ValueError;

final readonly class PolicyPage
{
    public function __construct(private PublicationStore $store, private ConfigurePolicy $configure, private LicenseDocumentUpload $documents, private AdminGuard $guard) {}

    public function register(): void
    {
        add_action('admin_menu', function (): void {
            $title = __('License and publication settings', 'librett-player-registry');
            add_submenu_page('librett-registry', $title, $title, 'librett_registry_manage_settings', 'librett-registry-policy', $this->render(...));
        });
        add_action('admin_post_librett_registry_policy', $this->submit(...));
    }

    public function render(): void
    {
        $this->guard->actor('librett_registry_manage_settings');
        $current = $this->store->policy();
        $policy = $current['policy'] ?? null;
        echo '<div class="wrap"><h1>' . esc_html__('License and publication settings', 'librett-player-registry') . '</h1>';
        echo '<p>' . esc_html__('No database license is selected automatically. Dataset licensing, publication authorization and photo rights are separate decisions. Changing this policy withdraws previous publications for a new review.', 'librett-player-registry') . '</p>';
        AdminGuard::form('librett_registry_policy', true);
        AdminGuard::hidden('revision', (string) ($current['revision'] ?? 0));
        AdminGuard::field('version', __('Policy version (change for each policy update)', 'librett-player-registry'), $policy->version ?? '', 64);
        AdminGuard::field('purpose', __('Publication purpose', 'librett-player-registry'), $policy->purpose ?? '', 1000);
        echo '<p><label>' . esc_html__('Database license', 'librett-player-registry') . '<br><select name="license" required><option value="">' . esc_html__('Choose a license', 'librett-player-registry') . '</option>';
        foreach (DatasetLicense::cases() as $license) {
            echo '<option value="' . esc_attr($license->value) . '"' . selected($policy?->license->value, $license->value, false) . '>' . esc_html($license->value) . '</option>';
        }
        echo '</select></label></p>';
        AdminGuard::field('dataset_terms', __('Dataset terms HTTPS URL (for Custom: URL or upload below)', 'librett-player-registry'), $policy !== null && $policy->documentKey === '' ? $policy->datasetTerms->value : '', 2048);
        echo '<p><label>' . esc_html__('Custom license upload: UTF-8 text or PDF, maximum 1 MiB. A saved document is retained when both inputs are empty.', 'librett-player-registry') . '<br><input type="file" name="license_document" accept=".pdf,.txt"></label></p>';
        AdminGuard::field('media_terms', __('Photo/media terms HTTPS URL', 'librett-player-registry'), $policy?->mediaTerms->value ?? '', 2048);
        AdminGuard::field('minor_policy', __('Documented minor publication policy (leave empty to block minor profiles)', 'librett-player-registry'), $policy->minorPolicy ?? '', 1000);
        submit_button(__('Save policy', 'librett-player-registry'));
        echo '</form></div>';
    }

    public function submit(): void
    {
        try {
            $actor = $this->guard->actor('librett_registry_manage_settings');
            $this->guard->post('librett_registry_policy');
            $license = DatasetLicense::from(DraftRequest::text($_POST['license'] ?? null, 40));
            $terms = DraftRequest::text($_POST['dataset_terms'] ?? '', 2048);
            $document = UploadRequest::bytes('license_document', 1048576);
            $key = '';
            if ($document !== null) {
                if ($license !== DatasetLicense::Custom || $terms !== '') {
                    throw new InvalidArgumentException('Choose Custom URL or upload.');
                }
                $key = $this->documents->store($document);
                $terms = rest_url('librett-registry/v1/license');
            } elseif ($license === DatasetLicense::Custom && $terms === '') {
                $previous = $this->store->policy();
                $key = $previous['policy']->documentKey ?? '';
                if ($key === '') {
                    throw new InvalidArgumentException('Custom license terms required.');
                }
                $terms = rest_url('librett-registry/v1/license');
            }
            if ($terms === '') {
                $terms = $license->standardTermsUrl() ?? '';
            }
            $policy = new Policy(
                DraftRequest::text($_POST['version'] ?? null, 256),
                DraftRequest::text($_POST['purpose'] ?? null, 4000),
                $license,
                new HttpsUrl($terms),
                new HttpsUrl(DraftRequest::text($_POST['media_terms'] ?? null, 2048)),
                DraftRequest::text($_POST['minor_policy'] ?? '', 4000),
                $key,
            );
            $this->configure->execute($actor, $policy, DraftRequest::counter($_POST['revision'] ?? null));
        } catch (RegistryFailure|InvalidArgumentException|ValueError) {
            wp_die(esc_html__('Settings were not saved. Check the current revision, policy version, HTTPS URLs and protected upload storage.', 'librett-player-registry'), '', ['response' => 400]);
        }
        wp_safe_redirect(admin_url('admin.php?page=librett-registry-policy'));
        exit;
    }
}
