<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

/** Additive private-draft migration; existing registries require operator backup confirmation. */
final readonly class DraftSchema
{
    public const string MIGRATION = '002_private_drafts';
    private const array DEFINITIONS = [
        'players' => [
            'entity_uuid' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'edit_revision' => 'bigint NOT NULL',
            'state' => 'varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'name' => 'varchar(200) NOT NULL',
            'country' => 'varchar(100) NOT NULL',
            'region' => 'varchar(100) NOT NULL',
            'given_name' => 'varchar(200) NOT NULL',
            'family_name' => 'varchar(200) NOT NULL',
            'biography' => 'text NOT NULL',
            'birth_year' => 'smallint NOT NULL',
        ],
        'clubs' => [
            'entity_uuid' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'edit_revision' => 'bigint NOT NULL',
            'state' => 'varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'name' => 'varchar(200) NOT NULL',
            'country' => 'varchar(100) NOT NULL',
            'region' => 'varchar(100) NOT NULL',
            'abbreviation' => 'varchar(32) NOT NULL',
        ],
    ];
    private const array AUDIT = [
        'id' => 'bigint NOT NULL AUTO_INCREMENT',
        'entity_uuid' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
        'edit_revision' => 'bigint NOT NULL',
        'actor_id' => 'bigint NOT NULL',
        'action' => 'varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
        'recorded_at' => 'timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)',
    ];

    public function __construct(private Database $db, private InitialSchema $initial) {}

    public function install(): void
    {
        $database = $this->db->rows('SELECT DATABASE() AS name');
        $lock = substr('librett:' . hash('sha256', (string) ($database[0]['name'] ?? '') . ':' . $this->db->connection->prefix), 0, 64);
        $rows = $this->db->rows($this->db->prepare('SELECT GET_LOCK(%s, 5) AS acquired', $lock));
        if ((int) ($rows[0]['acquired'] ?? 0) !== 1) {
            throw new RegistryFailure('migration_locked');
        }
        try {
            $this->initial->assertComplete();
            $migration = $this->migration();
            if ($migration !== null) {
                $this->assertMigration($migration);
                if ($migration['state'] === 'completed') {
                    $this->assertComplete();
                    return;
                }
            } else {
                $this->db->execute($this->db->prepare(
                    'INSERT INTO %i (migration_id, checksum, state) VALUES (%s, %s, %s)',
                    trim($this->db->table('migrations'), '`'), self::MIGRATION, $this->checksum(), 'started',
                ));
            }
            foreach ($this->definitions() as $table => $columns) {
                $sql = [];
                foreach ($columns as $name => $definition) {
                    $sql[] = '`' . $name . '` ' . $definition;
                }
                $sql[] = 'PRIMARY KEY (`' . ($this->isAudit($table) ? 'id' : 'entity_uuid') . '`)';
                $this->db->execute('CREATE TABLE IF NOT EXISTS ' . $this->db->table($table) . ' (' . implode(', ', $sql)
                    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                $this->verify($table, $columns);
            }
            $this->db->execute($this->db->prepare(
                'UPDATE %i SET state = %s WHERE migration_id = %s AND checksum = %s',
                trim($this->db->table('migrations'), '`'), 'completed', self::MIGRATION, $this->checksum(),
            ));
            $this->assertComplete();
        } finally {
            $this->db->rows($this->db->prepare('SELECT RELEASE_LOCK(%s) AS released', $lock));
        }
    }

    public function assertComplete(): void
    {
        $this->initial->assertComplete();
        $migration = $this->migration();
        if ($migration === null) {
            throw new RegistryFailure('draft_schema_unavailable');
        }
        $this->assertMigration($migration);
        if ($migration['state'] !== 'completed') {
            throw new RegistryFailure('draft_schema_unavailable');
        }
        foreach ($this->definitions() as $table => $columns) {
            $this->verify($table, $columns);
        }
    }

    /** @param array<string, int|string|null> $migration */
    private function assertMigration(array $migration): void
    {
        if (!hash_equals($this->checksum(), (string) $migration['checksum'])) {
            throw new RegistryFailure('migration_checksum_mismatch');
        }
        if (!in_array($migration['state'], ['started', 'completed'], true)) {
            throw new RegistryFailure('migration_state_invalid');
        }
    }

    /** @return array<string, int|string|null>|null */
    private function migration(): ?array
    {
        return $this->db->rows($this->db->prepare(
            'SELECT checksum, state FROM %i WHERE migration_id = %s',
            trim($this->db->table('migrations'), '`'), self::MIGRATION,
        ))[0] ?? null;
    }

    /** @return array<string, array<string, string>> */
    private function definitions(): array
    {
        return [...self::DEFINITIONS, 'players_audit' => self::AUDIT, 'clubs_audit' => self::AUDIT];
    }

    private function checksum(): string
    {
        return hash('sha256', serialize([$this->definitions(), 'InnoDB', 'utf8mb4_unicode_ci']));
    }

    private function isAudit(string $table): bool
    {
        return str_ends_with($table, '_audit');
    }

    /** @param array<string, string> $definitions */
    private function verify(string $table, array $definitions): void
    {
        $name = trim($this->db->table($table), '`');
        $status = $this->db->rows($this->db->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $name,
        ));
        if (count($status) !== 1 || strtolower((string) $status[0]['ENGINE']) !== 'innodb'
            || $status[0]['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            throw new RegistryFailure('schema_mismatch');
        }
        $columns = $this->db->rows('SHOW FULL COLUMNS FROM ' . $this->db->table($table));
        if (count($columns) !== count($definitions)) {
            throw new RegistryFailure('schema_mismatch');
        }
        foreach ($columns as $column) {
            $definition = $definitions[(string) $column['Field']] ?? null;
            if ($definition === null) {
                throw new RegistryFailure('schema_mismatch');
            }
            $type = preg_replace('/\b(smallint|bigint)\([0-9]+\)/', '$1', strtolower((string) $column['Type']));
            $text = str_starts_with($definition, 'varchar') || str_starts_with($definition, 'text') || str_starts_with($definition, 'char');
            $collation = str_contains($definition, 'ascii_bin') ? 'ascii_bin' : 'utf8mb4_unicode_ci';
            if ($type !== strtolower(explode(' ', $definition)[0]) || $column['Null'] !== 'NO'
                || ($text && $column['Collation'] !== $collation)
                || (str_contains($definition, 'AUTO_INCREMENT') !== str_contains((string) $column['Extra'], 'auto_increment'))
                || (str_contains($definition, 'CURRENT_TIMESTAMP')
                    ? strtolower((string) $column['Default']) !== 'current_timestamp(6)'
                    : $column['Default'] !== null)) {
                throw new RegistryFailure('schema_mismatch');
            }
        }
        $indexes = $this->db->rows('SHOW INDEX FROM ' . $this->db->table($table));
        if (count($indexes) !== 1 || $indexes[0]['Key_name'] !== 'PRIMARY'
            || $indexes[0]['Column_name'] !== ($this->isAudit($table) ? 'id' : 'entity_uuid') || (int) $indexes[0]['Non_unique'] !== 0) {
            throw new RegistryFailure('schema_mismatch');
        }
    }
}
