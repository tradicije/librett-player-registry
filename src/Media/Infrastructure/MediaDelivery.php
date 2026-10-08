<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SchemaGate;
use LibreTT\PlayerRegistry\Media\Application\PhotoCatalogue;
use LibreTT\PlayerRegistry\Media\Application\ProtectedFiles;
use LibreTT\PlayerRegistry\Publication\Application\PublicationStore;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use WP_Error;
use WP_REST_Request;

final readonly class MediaDelivery
{
    public function __construct(private PhotoCatalogue $photos, private ProtectedFiles $files, private PublicationStore $publications, private SchemaGate $schema) {}

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('librett-registry/v1', '/media/(?P<id>[0-9a-f-]{36})', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => $this->publicPhoto(...)]);
            register_rest_route('librett-registry/v1', '/private-media/(?P<id>[0-9a-f-]{36})', ['methods' => 'GET', 'permission_callback' => static fn(): bool => current_user_can('librett_registry_edit_profiles'), 'callback' => $this->privatePhoto(...)]);
            register_rest_route('librett-registry/v1', '/license', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => $this->license(...)]);
        });
        add_filter('rest_pre_serve_request', static function (bool $served, mixed $response, WP_REST_Request $request): bool {
            if ($response instanceof BinaryResponse) {
                if ($request->get_method() !== 'HEAD') {
                    echo $response->bytes;
                }
                return true;
            }
            return $served;
        }, 10, 3);
    }

    public function publicPhoto(WP_REST_Request $request): BinaryResponse|WP_Error
    {
        return $this->photo($request, true);
    }

    public function privatePhoto(WP_REST_Request $request): BinaryResponse|WP_Error
    {
        if (!current_user_can('librett_registry_edit_profiles')) {
            return new WP_Error('permission_denied', __('Permission denied.', 'librett-player-registry'), ['status' => 403]);
        }
        return $this->photo($request, false);
    }

    private function photo(WP_REST_Request $request, bool $public): BinaryResponse|WP_Error
    {
        try {
            $this->schema->assertComplete();
            $raw = $request->get_param('id');
            if (!is_string($raw)) {
                throw new InvalidArgumentException('Invalid media identity.');
            }
            $id = new EntityId($raw);
            if ($public && $this->publications->find('media', $id) === null) {
                throw new RegistryFailure('media_unavailable');
            }
            $photo = $this->photos->find($id);
            if ($photo === null) {
                throw new RegistryFailure('media_unavailable');
            }
            $bytes = $this->files->read($photo->key, 5242880);
            if (strlen($bytes) !== $photo->length || !hash_equals($photo->digest, hash('sha256', $bytes))) {
                throw new RegistryFailure('media_unavailable');
            }
            return new BinaryResponse($bytes, $photo->mime);
        } catch (RegistryFailure|InvalidArgumentException) {
            return new WP_Error('media_not_found', __('Photo unavailable.', 'librett-player-registry'), ['status' => 404]);
        }
    }

    public function license(): BinaryResponse|WP_Error
    {
        try {
            $this->schema->assertComplete();
            $policy = $this->publications->policy();
            $key = $policy['policy']->documentKey ?? '';
            if ($key === '') {
                throw new RegistryFailure('license_unavailable');
            }
            return new BinaryResponse($this->files->read($key, 1048576), str_ends_with($key, '.pdf') ? 'application/pdf' : 'text/plain; charset=UTF-8', str_ends_with($key, '.pdf') ? 'license.pdf' : 'license.txt');
        } catch (RegistryFailure) {
            return new WP_Error('license_not_found', __('License document unavailable.', 'librett-player-registry'), ['status' => 404]);
        }
    }
}
