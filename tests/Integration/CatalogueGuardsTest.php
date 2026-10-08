<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\AdminGuard;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Preflight;
use LibreTT\PlayerRegistry\Media\Infrastructure\LocalProtectedFiles;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use RuntimeException;

final class CatalogueGuardsTest extends CatalogueTestCase
{
    public function testAcceptedPhpBranchesUseTheSameWordPressPreflight(): void
    {
        $preflight = new Preflight();
        foreach (['8.3.3', '8.3.30', '8.4.0', '8.5.11'] as $version) {
            $preflight->assertReady($version, 8);
            $this->addToAssertionCount(1);
        }
    }

    public function testAdditiveSchemaResumeAndChecksumProtection(): void
    {
        $identity = $this->registry->current()->id->value;
        $this->db->execute("UPDATE " . $this->db->table('migrations') . " SET state = 'started' WHERE migration_id = '006_snapshot_import'");
        $this->schema->install();
        $this->schema->install();
        self::assertSame($identity, $this->registry->current()->id->value);
        self::assertCount(6, $this->db->rows('SELECT migration_id FROM ' . $this->db->table('migrations')));
        $this->db->execute("UPDATE " . $this->db->table('migrations') . " SET checksum = REPEAT('0', 64) WHERE migration_id = '006_snapshot_import'");
        try {
            $this->schema->assertComplete();
            self::fail('Changed checksum accepted.');
        } catch (RegistryFailure $failure) {
            self::assertSame('migration_checksum_mismatch', $failure->errorCode);
        }
    }

    public function testMutationGuardRejectsGETAndInvalidNonce(): void
    {
        $guard = new AdminGuard($this->schema, new Preflight());
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $request = $_REQUEST;
        $handler = static fn() => static function (): never {
            throw new RuntimeException('request_rejected');
        };
        add_filter('wp_die_handler', $handler);
        try {
            foreach (['GET', 'POST'] as $verb) {
                $_SERVER['REQUEST_METHOD'] = $verb;
                $_REQUEST = ['_wpnonce' => 'invalid'];
                try {
                    $guard->post('librett_registry_publication');
                    self::fail('Invalid request accepted.');
                } catch (RuntimeException $failure) {
                    self::assertSame('request_rejected', $failure->getMessage());
                }
            }
        } finally {
            remove_filter('wp_die_handler', $handler);
            $_REQUEST = $request;
            if ($method === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $method;
            }
        }
    }

    public function testAnonymousMutationAndPublicDirectoryStorageAreRejected(): void
    {
        wp_set_current_user(0);
        $handler = static fn() => static function (): never {
            throw new RuntimeException('permission_rejected');
        };
        add_filter('wp_die_handler', $handler);
        try {
            (new AdminGuard($this->schema, new Preflight()))->actor('librett_registry_publish_profiles');
            self::fail('Anonymous actor accepted.');
        } catch (RuntimeException $failure) {
            self::assertSame('permission_rejected', $failure->getMessage());
        } finally {
            remove_filter('wp_die_handler', $handler);
        }
        $this->expectException(RegistryFailure::class);
        (new LocalProtectedFiles(ABSPATH, ABSPATH))->put('private terms', 'txt');
    }
}
