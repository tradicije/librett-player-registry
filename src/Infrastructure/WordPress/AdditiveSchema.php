<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final readonly class AdditiveSchema implements SchemaMigration
{
    /** @param array<string, SqlTable> $tables */
    public function __construct(private Database $db, public string $id, private SchemaGate $previous, private array $tables) {}

    public function install(): void
    {
        $database = $this->db->rows('SELECT DATABASE() AS name');
        $lock = substr('librett:' . hash('sha256', (string) ($database[0]['name'] ?? '') . ':' . $this->db->connection->prefix), 0, 64);
        $rows = $this->db->rows($this->db->prepare('SELECT GET_LOCK(%s, 5) AS acquired', $lock));
        if ((int) ($rows[0]['acquired'] ?? 0) !== 1) {
            throw new RegistryFailure('migration_locked');
        }
        try {
            $this->previous->assertComplete();
            $existing = $this->migration();
            if ($existing !== null) {
                $this->checkMigration($existing);
                if ($existing['state'] === 'completed') {
                    $this->assertComplete();
                    return;
                }
            } else {
                $this->db->execute($this->db->prepare('INSERT INTO %i (migration_id, checksum, state) VALUES (%s, %s, %s)', trim($this->db->table('migrations'), '`'), $this->id, $this->checksum(), 'started'));
            }
            foreach ($this->tables as $name => $table) {
                $columns = [];
                foreach ($table->columns as $column => $definition) {
                    $columns[] = '`' . $column . '` ' . $definition;
                }
                $columns[] = 'PRIMARY KEY (`' . implode('`, `', $table->primary) . '`)';
                $this->db->execute('CREATE TABLE IF NOT EXISTS ' . $this->db->table($name) . ' (' . implode(', ', $columns) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                $this->verify($name, $table);
            }
            $this->db->execute($this->db->prepare('UPDATE %i SET state = %s WHERE migration_id = %s AND checksum = %s', trim($this->db->table('migrations'), '`'), 'completed', $this->id, $this->checksum()));
            $this->assertComplete();
        } finally {
            $this->db->rows($this->db->prepare('SELECT RELEASE_LOCK(%s) AS released', $lock));
        }
    }

    public function assertComplete(): void
    {
        $this->previous->assertComplete();
        $migration = $this->migration();
        if ($migration === null || $migration['state'] !== 'completed') {
            throw new RegistryFailure('schema_unavailable');
        }
        $this->checkMigration($migration);
        foreach ($this->tables as $name => $table) {
            $this->verify($name, $table);
        }
    }

    private function checksum(): string
    {
        return hash('sha256', serialize([$this->id, $this->tables, 'InnoDB', 'utf8mb4_unicode_ci']));
    }

    /** @return array<string, int|string|null>|null */
    private function migration(): ?array
    {
        return $this->db->rows($this->db->prepare('SELECT checksum, state FROM %i WHERE migration_id = %s', trim($this->db->table('migrations'), '`'), $this->id))[0] ?? null;
    }

    /** @param array<string, int|string|null> $migration */
    private function checkMigration(array $migration): void
    {
        if (!hash_equals($this->checksum(), (string) $migration['checksum'])) {
            throw new RegistryFailure('migration_checksum_mismatch');
        }
        if (!in_array($migration['state'], ['started', 'completed'], true)) {
            throw new RegistryFailure('migration_state_invalid');
        }
    }

    private function verify(string $name, SqlTable $table): void
    {
        $status = $this->db->rows($this->db->prepare('SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', trim($this->db->table($name), '`')));
        if (count($status) !== 1 || strtolower((string) $status[0]['ENGINE']) !== 'innodb' || $status[0]['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            throw new RegistryFailure('schema_mismatch');
        }
        $columns = $this->db->rows('SHOW FULL COLUMNS FROM ' . $this->db->table($name));
        if (count($columns) !== count($table->columns)) {
            throw new RegistryFailure('schema_mismatch');
        }
        foreach ($columns as $column) {
            $definition = $table->columns[(string) $column['Field']] ?? null;
            if ($definition === null) {
                throw new RegistryFailure('schema_mismatch');
            }
            $type = preg_replace('/\b(tinyint|smallint|bigint|int)\([0-9]+\)/', '$1', strtolower((string) $column['Type']));
            $text = preg_match('/\A(?:var)?char|\A(?:long)?text/', $definition) === 1;
            $collation = str_contains($definition, 'ascii_bin') ? 'ascii_bin' : (str_contains($definition, 'utf8mb4_bin') ? 'utf8mb4_bin' : 'utf8mb4_unicode_ci');
            $expectedType = strtolower(explode(' ', $definition)[0]) . (str_contains($definition, ' unsigned ') ? ' unsigned' : '');
            if ($type !== $expectedType || $column['Null'] !== 'NO'
                || ($text && $column['Collation'] !== $collation)
                || (str_contains($definition, 'AUTO_INCREMENT') !== str_contains((string) $column['Extra'], 'auto_increment'))
                || (str_contains($definition, 'CURRENT_TIMESTAMP') ? strtolower((string) $column['Default']) !== 'current_timestamp(6)' : $column['Default'] !== null)) {
                throw new RegistryFailure('schema_mismatch');
            }
        }
        $indexes = $this->db->rows('SHOW INDEX FROM ' . $this->db->table($name));
        if (count($indexes) !== count($table->primary)) {
            throw new RegistryFailure('schema_mismatch');
        }
        foreach ($indexes as $index) {
            $position = (int) $index['Seq_in_index'] - 1;
            if ($index['Key_name'] !== 'PRIMARY' || (int) $index['Non_unique'] !== 0 || ($table->primary[$position] ?? null) !== $index['Column_name']) {
                throw new RegistryFailure('schema_mismatch');
            }
        }
    }
}
