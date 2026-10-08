<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure;

use InvalidArgumentException;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SchemaGate;
use LibreTT\PlayerRegistry\Publication\Application\ExportSnapshot;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\Shared\Domain\EntityId;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final readonly class PublicApi
{
    public function __construct(private ExportSnapshot $export, private SchemaGate $schema) {}

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            foreach (['manifest', 'snapshot', 'players', 'clubs', 'players/(?P<id>[0-9a-f-]{36})', 'clubs/(?P<id>[0-9a-f-]{36})'] as $route) {
                register_rest_route('librett-registry/v1', '/' . $route, ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => $this->handle(...)]);
            }
        });
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $this->schema->assertComplete();
            $snapshot = $this->export->execute();
            $route = $request->get_route();
            if (str_ends_with($route, '/snapshot')) {
                $data = $snapshot;
            } elseif (str_ends_with($route, '/manifest')) {
                $data = ['format' => $snapshot->format, 'schema_version' => 1, 'registry_id' => $snapshot->registry_id,
                    'registry_name' => $snapshot->registry_name, 'checkpoint' => $snapshot->checkpoint, 'authority_generation' => null,
                    'publication_policy' => $snapshot->publication_policy, 'snapshot_url' => rest_url('librett-registry/v1/snapshot'),
                    'authentication' => 'unsigned'];
            } else {
                $type = str_contains($route, '/players') ? 'players' : 'clubs';
                $id = $request->get_param('id');
                if ($id !== null) {
                    if (!is_string($id)) {
                        throw new InvalidArgumentException('Invalid identity.');
                    }
                    new EntityId($id);
                    $data = null;
                    foreach (($type === 'players' ? $snapshot->players : $snapshot->clubs) as $record) {
                        if ($record->id === $id) {
                            $data = $record;
                            break;
                        }
                    }
                    if ($data === null) {
                        return new WP_Error('profile_not_found', __('Public profile not found.', 'librett-player-registry'), ['status' => 404]);
                    }
                } else {
                    $query = $request->get_param('q') ?? '';
                    if (!is_string($query)) {
                        throw new InvalidArgumentException('Invalid search.');
                    }
                    PlainText::assertValid($query, 200);
                    $offset = $this->counter($request->get_param('offset') ?? '0', 100000);
                    $limit = $this->counter($request->get_param('limit') ?? '50', 50);
                    if ($limit < 1) {
                        throw new InvalidArgumentException('Invalid page size.');
                    }
                    $items = array_values(array_filter(($type === 'players' ? $snapshot->players : $snapshot->clubs), static function ($record) use ($query): bool {
                        $name = $record->display_name ?? $record->name;
                        return is_string($name) && mb_stripos($name, $query) !== false;
                    }));
                    $data = ['registry_id' => $snapshot->registry_id, 'checkpoint' => $snapshot->checkpoint, 'items' => array_slice($items, $offset, $limit)];
                }
            }
            $response = new WP_REST_Response($data);
            $response->header('Cache-Control', 'no-store');
            $response->header('X-Content-Type-Options', 'nosniff');
            return $response;
        } catch (RegistryFailure) {
            return new WP_Error('registry_unavailable', __('Public catalogue is unavailable.', 'librett-player-registry'), ['status' => 503]);
        } catch (InvalidArgumentException) {
            return new WP_Error('invalid_request', __('Invalid catalogue request.', 'librett-player-registry'), ['status' => 400]);
        }
    }

    private function counter(mixed $value, int $maximum): int
    {
        if (!is_string($value) || preg_match('/\A(?:0|[1-9][0-9]{0,5})\z/', $value) !== 1 || (int) $value > $maximum) {
            throw new InvalidArgumentException('Invalid page.');
        }
        return (int) $value;
    }
}
