<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final readonly class InitialSchema implements SchemaMigration
{
    private const string MIGRATION = '001_identity_setup';
    private const array DEFINITIONS = [
        'migrations' => [
            'migration_id' => "varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL",
            'checksum' => "char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL",
            'state' => "varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL",
        ],
        'identity' => [
            'singleton' => 'tinyint NOT NULL',
            'registry_uuid' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NULL',
            'name' => 'varchar(200) NULL',
            'role' => 'varchar(16) CHARACTER SET ascii COLLATE ascii_bin NULL',
        ],
        'identity_audit' => [
            'id' => 'bigint NOT NULL AUTO_INCREMENT',
            'actor_id' => 'bigint NOT NULL',
            'action' => 'varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'recorded_at' => 'timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)',
        ],
    ];
    private const array PRIMARY_KEYS = ['migrations' => 'migration_id', 'identity' => 'singleton', 'identity_audit' => 'id'];

    public function __construct(private Database $db) {}

    public function install(): void
    {
        $database = $this->db->rows('SELECT DATABASE() AS name');
        $lock = 'librett:' . hash('sha256', (string) ($database[0]['name'] ?? '') . ':' . $this->db->connection->prefix);
        $lock = substr($lock, 0, 64);
        $rows = $this->db->rows($this->db->prepare('SELECT GET_LOCK(%s, 5) AS acquired', $lock));
        if ((int) ($rows[0]['acquired'] ?? 0) !== 1) {
            throw new RegistryFailure('migration_locked');
        }
        try {
            $this->assertDatabaseTarget();
            $this->create('migrations');
            $this->verify('migrations');
            $all = $this->db->rows('SELECT migration_id FROM ' . $this->db->table('migrations'));
            foreach ($all as $row) {
                if (!in_array($row['migration_id'], MigrationIds::ALL, true)) {
                    throw new RegistryFailure('unsupported_schema_version');
                }
            }
            $existing = $this->migration();
            if ($existing !== null && !hash_equals($this->checksum(), (string) $existing['checksum'])) {
                throw new RegistryFailure('migration_checksum_mismatch');
            }
            if ($existing !== null && !in_array($existing['state'], ['started', 'completed'], true)) {
                throw new RegistryFailure('migration_state_invalid');
            }
            if ($existing !== null && $existing['state'] === 'completed') {
                $this->assertComplete();
                return;
            }
            if ($existing === null) {
                $this->db->execute($this->db->prepare(
                    'INSERT INTO %i (migration_id, checksum, state) VALUES (%s, %s, %s)',
                    trim($this->db->table('migrations'), '`'),
                    self::MIGRATION,
                    $this->checksum(),
                    'started',
                ));
            }
            foreach (['identity', 'identity_audit'] as $table) {
                $this->create($table);
                $this->verify($table);
            }
            $this->db->run(function (): void {
                $rows = $this->db->rows('SELECT singleton FROM ' . $this->db->table('identity') . ' FOR UPDATE');
                if ($rows === []) {
                    $this->db->execute('INSERT INTO ' . $this->db->table('identity') . ' (singleton) VALUES (1)');
                } elseif (count($rows) !== 1 || (int) $rows[0]['singleton'] !== 1) {
                    throw new RegistryFailure('invalid_setup_state');
                }
                $this->db->execute($this->db->prepare(
                    'UPDATE %i SET state = %s WHERE migration_id = %s AND checksum = %s',
                    trim($this->db->table('migrations'), '`'),
                    'completed',
                    self::MIGRATION,
                    $this->checksum(),
                ));
            });
        } finally {
            $this->db->rows($this->db->prepare('SELECT RELEASE_LOCK(%s) AS released', $lock));
        }
    }

    public function assertComplete(): void
    {
        $this->verify('migrations');
        $migration = $this->migration();
        if ($migration === null || $migration['state'] !== 'completed'
            || !hash_equals($this->checksum(), (string) $migration['checksum'])) {
            throw new RegistryFailure('schema_unavailable');
        }
        $all = $this->db->rows('SELECT migration_id FROM ' . $this->db->table('migrations'));
        foreach ($all as $row) {
            if (!in_array($row['migration_id'], MigrationIds::ALL, true)) {
                throw new RegistryFailure('unsupported_schema_version');
            }
        }
        $this->verify('identity');
        $this->verify('identity_audit');
        $rows = $this->db->rows('SELECT singleton FROM ' . $this->db->table('identity'));
        if (count($rows) !== 1 || (int) $rows[0]['singleton'] !== 1) {
            throw new RegistryFailure('invalid_setup_state');
        }
    }

    private function assertDatabaseTarget(): void
    {
        $rows = $this->db->rows('SELECT VERSION() AS version');
        $version = (string) ($rows[0]['version'] ?? '');
        $maria = stripos($version, 'MariaDB') !== false;
        if (preg_match('/(?:5\.5\.5-)?([0-9]+\.[0-9]+)\.[0-9]+/', $version, $matches) !== 1
            || $matches[1] !== ($maria ? '10.11' : '8.4')) {
            throw new RegistryFailure('unsupported_database');
        }
        $engines = $this->db->rows('SHOW ENGINES');
        foreach ($engines as $engine) {
            if (strtolower((string) $engine['Engine']) === 'innodb'
                && in_array(strtoupper((string) $engine['Support']), ['YES', 'DEFAULT'], true)) {
                return;
            }
        }
        throw new RegistryFailure('transactional_engine_unavailable');
    }

    private function checksum(): string
    {
        return hash('sha256', serialize([self::DEFINITIONS, self::PRIMARY_KEYS, 'InnoDB', 'utf8mb4_unicode_ci']));
    }

    /** @return array<string, int|string|null>|null */
    private function migration(): ?array
    {
        $rows = $this->db->rows($this->db->prepare(
            'SELECT checksum, state FROM %i WHERE migration_id = %s',
            trim($this->db->table('migrations'), '`'),
            self::MIGRATION,
        ));
        return $rows[0] ?? null;
    }

    private function create(string $table): void
    {
        $columns = [];
        foreach (self::DEFINITIONS[$table] as $name => $definition) {
            $columns[] = '`' . $name . '` ' . $definition;
        }
        $columns[] = 'PRIMARY KEY (`' . self::PRIMARY_KEYS[$table] . '`)';
        $this->db->execute('CREATE TABLE IF NOT EXISTS ' . $this->db->table($table) . ' (' . implode(', ', $columns)
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function verify(string $table): void
    {
        $name = trim($this->db->table($table), '`');
        $status = $this->db->rows($this->db->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
            $name,
        ));
        if (count($status) !== 1 || strtolower((string) $status[0]['ENGINE']) !== 'innodb'
            || $status[0]['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            throw new RegistryFailure('schema_mismatch');
        }
        $columns = $this->db->rows('SHOW FULL COLUMNS FROM ' . $this->db->table($table));
        if (count($columns) !== count(self::DEFINITIONS[$table])) {
            throw new RegistryFailure('schema_mismatch');
        }
        foreach ($columns as $column) {
            $definition = self::DEFINITIONS[$table][(string) $column['Field']] ?? null;
            if ($definition === null) {
                throw new RegistryFailure('schema_mismatch');
            }
            $type = strtolower((string) $column['Type']);
            $type = preg_replace('/\b(tinyint|bigint)\([0-9]+\)/', '$1', $type);
            $expectedType = strtolower(explode(' ', $definition)[0]);
            $nullable = !str_contains($definition, 'NOT NULL');
            if ($type !== $expectedType || ($column['Null'] === 'YES') !== $nullable
                || (str_contains($definition, 'ascii_bin') && $column['Collation'] !== 'ascii_bin')
                || (str_contains($definition, 'AUTO_INCREMENT') !== str_contains((string) $column['Extra'], 'auto_increment'))
                || (str_contains($definition, 'CURRENT_TIMESTAMP')
                    ? strtolower((string) $column['Default']) !== 'current_timestamp(6)'
                    : $column['Default'] !== null)
                || (str_starts_with($definition, 'varchar(200)') && $column['Collation'] !== 'utf8mb4_unicode_ci')) {
                throw new RegistryFailure('schema_mismatch');
            }
        }
        $indexes = $this->db->rows('SHOW INDEX FROM ' . $this->db->table($table));
        if (count($indexes) !== 1 || $indexes[0]['Key_name'] !== 'PRIMARY'
            || $indexes[0]['Column_name'] !== self::PRIMARY_KEYS[$table] || (int) $indexes[0]['Non_unique'] !== 0) {
            throw new RegistryFailure('schema_mismatch');
        }
    }
}
