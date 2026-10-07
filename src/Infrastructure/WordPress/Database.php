<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\UnitOfWork;
use Throwable;
use wpdb;

final class Database implements UnitOfWork
{
    private bool $inTransaction = false;

    public function __construct(public readonly wpdb $connection) {}

    public function table(string $suffix): string
    {
        $name = $this->connection->prefix . 'librett_registry_' . $suffix;
        if (preg_match('/\A[a-zA-Z0-9_]+\z/', $name) !== 1) {
            throw new RegistryFailure('invalid_table_prefix');
        }
        return '`' . $name . '`';
    }

    /** @param literal-string $query */
    public function prepare(string $query, int|string ...$arguments): string
    {
        $sql = $this->connection->prepare($query, ...$arguments);
        if (!is_string($sql)) {
            throw new RegistryFailure('invalid_query');
        }
        return $sql;
    }

    public function execute(string $sql): int|bool
    {
        // Prevent WordPress database errors leaking private query contents into responses.
        $previous = $this->connection->suppress_errors(true);
        try {
            $result = $this->connection->query($sql);
            if ($result === false) {
                throw new RegistryFailure('storage_failure');
            }
            return $result;
        } finally {
            $this->connection->suppress_errors($previous);
        }
    }

    /** @return list<array<string, int|string|null>> */
    public function rows(string $sql): array
    {
        $this->execute($sql);
        $rows = [];
        foreach ($this->connection->last_result ?? [] as $row) {
            $values = [];
            foreach (get_object_vars($row) as $key => $value) {
                if (!is_string($key) || (!is_int($value) && !is_string($value) && $value !== null)) {
                    throw new RegistryFailure('invalid_query_result');
                }
                $values[$key] = $value;
            }
            $rows[] = $values;
        }
        return $rows;
    }

    public function run(callable $operation): void
    {
        if ($this->inTransaction) {
            throw new RegistryFailure('nested_transaction');
        }
        $this->execute('START TRANSACTION');
        $this->inTransaction = true;
        try {
            $operation();
            $this->execute('COMMIT');
        } catch (Throwable $failure) {
            try {
                $this->execute('ROLLBACK');
            } catch (Throwable) {
                // Preserve the originating failure; caller sees only a stable error code.
            }
            throw $failure;
        } finally {
            $this->inTransaction = false;
        }
    }
}
