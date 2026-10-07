<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Tests\Integration;

use LibreTT\PlayerRegistry\Infrastructure\WordPress\Database;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\InitialSchema;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\Preflight;
use LibreTT\PlayerRegistry\Infrastructure\WordPress\SetupPage;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator;
use LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\WordPressRegistryRepository;
use PHPUnit\Framework\TestCase;
use wpdb;

final class BootstrapTest extends TestCase
{
    private Database $db;
    private InitialSchema $schema;
    private WordPressRegistryRepository $repository;
    private CreateRegistry $create;

    protected function setUp(): void
    {
        $connection = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $connection->set_prefix('ltt_bootstrap_' . bin2hex(random_bytes(4)) . '_');
        $this->db = new Database($connection);
        $this->schema = new InitialSchema($this->db);
        $this->repository = new WordPressRegistryRepository($this->db);
        $this->create = new CreateRegistry($this->repository, $this->db, new RamseyUuidGenerator());
    }

    protected function tearDown(): void
    {
        foreach (['identity_audit', 'identity', 'migrations'] as $name) {
            $this->db->execute('DROP TABLE IF EXISTS ' . $this->db->table($name));
        }
        $this->db->connection->close();
        wp_set_current_user(0);
    }

    private function actor(): Actor
    {
        return new Actor(1, ['librett_registry_manage_settings']);
    }

    public function testActivationSchemaIsEmptyAndReentrant(): void
    {
        $this->schema->install();
        $this->schema->install();
        $this->schema->assertComplete();
        self::assertNull($this->repository->current());
        self::assertCount(0, $this->db->rows('SELECT * FROM ' . $this->db->table('identity_audit')));
        $tables = $this->db->rows($this->db->connection->prepare(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE %s',
            $this->db->connection->esc_like($this->db->connection->prefix . 'librett_registry_') . '%',
        ));
        self::assertCount(3, $tables);
    }

    public function testSetupPersistsIdentityAndAuditAndCannotReplaceExistingRegistry(): void
    {
        $this->schema->install();
        $registry = $this->create->execute($this->actor(), 'Example registry');
        self::assertSame($registry->id->value, $this->repository->current()?->id->value);
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('identity_audit')));
        try {
            $this->create->execute($this->actor(), 'Different registry');
            self::fail('Repeated setup must conflict.');
        } catch (RegistryFailure $failure) {
            self::assertSame('identity_conflict', $failure->errorCode);
        }
        self::assertSame($registry->id->value, $this->repository->current()?->id->value);
        self::assertCount(1, $this->db->rows('SELECT * FROM ' . $this->db->table('identity_audit')));
        $this->schema->install();
        self::assertSame($registry->id->value, $this->repository->current()?->id->value);
    }

    public function testFailedAuditRollsBackIdentityCreation(): void
    {
        $this->schema->install();
        $table = $this->db->table('identity_audit');
        $temporary = $this->db->table('audit_unavailable');
        $this->db->execute('RENAME TABLE ' . $table . ' TO ' . $temporary);
        try {
            $this->create->execute($this->actor(), 'Failed setup');
            self::fail('Missing audit storage must abort setup.');
        } catch (RegistryFailure $failure) {
            self::assertSame('storage_failure', $failure->errorCode);
        } finally {
            $this->db->execute('RENAME TABLE ' . $temporary . ' TO ' . $table);
        }
        self::assertNull($this->repository->current());
        self::assertCount(0, $this->db->rows('SELECT * FROM ' . $table));
    }

    public function testInterruptedInitialDdlCanResume(): void
    {
        $this->schema->install();
        $this->db->execute('DROP TABLE ' . $this->db->table('identity_audit'));
        $this->db->execute("UPDATE " . $this->db->table('migrations') . " SET state = 'started'");
        $this->schema->install();
        $this->schema->assertComplete();
        self::assertNull($this->repository->current());
    }

    public function testChecksumMismatchIsRejectedWithoutChangingIdentity(): void
    {
        $this->schema->install();
        $registry = $this->create->execute($this->actor(), 'Preserved');
        $this->db->execute($this->db->connection->prepare('UPDATE ' . $this->db->table('migrations') . ' SET checksum = %s', str_repeat('0', 64)));
        try {
            $this->schema->install();
            self::fail('Changed checksum must be rejected.');
        } catch (RegistryFailure $failure) {
            self::assertSame('migration_checksum_mismatch', $failure->errorCode);
        }
        self::assertSame($registry->id->value, $this->repository->current()?->id->value);
    }

    public function testNontransactionalExistingTableIsRejected(): void
    {
        $this->schema->install();
        $this->db->execute('ALTER TABLE ' . $this->db->table('identity') . ' ENGINE=MyISAM');
        $this->expectException(RegistryFailure::class);
        $this->expectExceptionMessage('schema_mismatch');
        $this->schema->install();
    }

    public function testFutureMigrationVersionIsRejected(): void
    {
        $this->schema->install();
        $this->db->execute($this->db->connection->prepare(
            'INSERT INTO ' . $this->db->table('migrations') . ' VALUES (%s, %s, %s)',
            '002_future',
            str_repeat('0', 64),
            'completed',
        ));
        $this->expectException(RegistryFailure::class);
        $this->expectExceptionMessage('unsupported_schema_version');
        $this->schema->install();
    }

    public function testFutureMigrationBlocksInterruptedInstallation(): void
    {
        $this->schema->install();
        $this->db->execute($this->db->connection->prepare(
            'INSERT INTO ' . $this->db->table('migrations') . ' VALUES (%s, %s, %s)',
            '002_future',
            str_repeat('0', 64),
            'completed',
        ));
        $this->db->execute('UPDATE ' . $this->db->table('migrations') . " SET state = 'started' WHERE migration_id = '001_identity_setup'");
        $this->expectException(RegistryFailure::class);
        $this->expectExceptionMessage('unsupported_schema_version');
        $this->schema->install();
    }

    public function testNestedTransactionRollsBackAndReleasesConnection(): void
    {
        $this->schema->install();
        try {
            $this->db->run(function (): void {
                $this->db->execute('UPDATE ' . $this->db->table('identity') . " SET name = 'not committed'");
                $this->db->run(static function (): void {});
            });
            self::fail('Nested transaction must not implicitly commit.');
        } catch (RegistryFailure $failure) {
            self::assertSame('nested_transaction', $failure->errorCode);
        }
        self::assertNull($this->repository->current());
        $this->db->run(static function (): void {});
    }

    public function testSetupRejectsInvalidNonceForAuthorizedUser(): void
    {
        $this->schema->install();
        $user = get_user_by('login', 'registry-test-admin');
        self::assertNotFalse($user);
        $user->add_cap('librett_registry_manage_settings');
        wp_set_current_user($user->ID);
        $page = new SetupPage($this->repository, $this->create, $this->schema, new Preflight());
        $previousRequest = $_REQUEST;
        $previousPost = $_POST;
        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = ['_wpnonce' => 'invalid'];
        $_POST = ['registry_name' => 'Never created'];
        $handler = static fn(): \Closure => static function (): never {
            throw new \RuntimeException('nonce_rejected');
        };
        add_filter('wp_die_handler', $handler);
        try {
            $page->submit();
            self::fail('Invalid nonce must fail.');
        } catch (\RuntimeException $failure) {
            self::assertSame('nonce_rejected', $failure->getMessage());
        } finally {
            remove_filter('wp_die_handler', $handler);
            $_REQUEST = $previousRequest;
            $_POST = $previousPost;
            if ($previousMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previousMethod;
            }
        }
        self::assertNull($this->repository->current());
    }

    public function testValidNonceCreatesRegistryAndEscapesItsName(): void
    {
        $this->schema->install();
        $user = get_user_by('login', 'registry-test-admin');
        self::assertNotFalse($user);
        $user->add_cap('librett_registry_manage_settings');
        wp_set_current_user($user->ID);
        $page = new SetupPage($this->repository, $this->create, $this->schema, new Preflight());
        $previousRequest = $_REQUEST;
        $previousPost = $_POST;
        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = ['_wpnonce' => wp_create_nonce('librett_registry_setup')];
        $_POST = ['registry_name' => '<script>Example</script>'];
        $redirect = static function (): never {
            throw new \RuntimeException('redirect_complete');
        };
        add_filter('wp_redirect', $redirect);
        try {
            $page->submit();
            self::fail('Successful setup must redirect.');
        } catch (\RuntimeException $failure) {
            self::assertSame('redirect_complete', $failure->getMessage());
        } finally {
            remove_filter('wp_redirect', $redirect);
            $_REQUEST = $previousRequest;
            $_POST = $previousPost;
            if ($previousMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previousMethod;
            }
        }
        self::assertSame('<script>Example</script>', $this->repository->current()?->name);
        ob_start();
        $page->render();
        $html = ob_get_clean();
        self::assertIsString($html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testWordPressActivationAndDeactivationPreserveIdentity(): void
    {
        global $wpdb;
        $user = get_user_by('login', 'registry-test-admin');
        self::assertNotFalse($user);
        wp_set_current_user($user->ID);
        $plugin = 'librett-player-registry/librett-player-registry.php';
        self::assertNull(activate_plugin($plugin));
        self::assertTrue(current_user_can('librett_registry_manage_settings'));
        $repository = new WordPressRegistryRepository(new Database($wpdb));
        $registry = $repository->current();
        if ($registry === null) {
            $database = new Database($wpdb);
            $registry = (new CreateRegistry($repository, $database, new RamseyUuidGenerator()))->execute(
                new Actor($user->ID, ['librett_registry_manage_settings']),
                'Lifecycle example',
            );
        }
        deactivate_plugins($plugin);
        self::assertSame($registry->id->value, $repository->current()?->id->value);
        self::assertNull(activate_plugin($plugin));
        self::assertSame($registry->id->value, $repository->current()?->id->value);
        deactivate_plugins($plugin);
        uninstall_plugin($plugin);
        self::assertSame($registry->id->value, $repository->current()?->id->value);
    }

    public function testConcurrentMigrationLockFailsWithoutCreatingSchema(): void
    {
        $other = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $lock = substr('librett:' . hash('sha256', DB_NAME . ':' . $this->db->connection->prefix), 0, 64);
        $other->get_var($other->prepare('SELECT GET_LOCK(%s, 0)', $lock));
        try {
            $this->schema->install();
            self::fail('Another migration owner must exclude this connection.');
        } catch (RegistryFailure $failure) {
            self::assertSame('migration_locked', $failure->errorCode);
        } finally {
            $other->get_var($other->prepare('SELECT RELEASE_LOCK(%s)', $lock));
            $other->close();
        }
        $this->schema->install();
        self::assertNull($this->repository->current());
    }

    public function testSetupPageDoesNotRevealStateToUnauthorizedUser(): void
    {
        $page = new SetupPage($this->repository, $this->create, $this->schema, new Preflight());
        wp_set_current_user(0);
        $handler = static fn(): \Closure => static function (string $message): never {
            throw new \RuntimeException(strip_tags($message));
        };
        add_filter('wp_die_handler', $handler);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Permission denied');
            $page->render();
        } finally {
            remove_filter('wp_die_handler', $handler);
        }
    }
}
