<?php

namespace Selpol\Service;

use Selpol\Framework\Client\Client;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Service\Clickhouse\ClickhouseEntityConnection;
use Throwable;

#[Singleton]
readonly class ClickhouseService
{
    private ClickhouseEntityConnection $connection;

    function __construct()
    {
        $plog = config_get('feature.plog');

        $this->connection = new ClickhouseEntityConnection(new Client(), 'http://' . $plog['host'] . ':' . $plog['port'] . '?database=' . $plog['database'], $plog['username'], $plog['password']);
    }

    function statement(string $query): EntityStatementInterface
    {
        return $this->connection->statement($query);
    }

    function select(string $query): array|bool
    {
        try {
            $statement = $this->statement($query);

            if ($statement->execute()) {
                return $statement->fetchAll();
            }
        } catch (Throwable $throwable) {
            file_logger('clickhouse')->error($throwable);
        }

        return false;
    }

    function insert(string $table, array $data): bool|string
    {
        try {
            $columns = array_keys($data);

            return $this->statement('INSERT INTO ' . $table . ' (' . join(', ', $columns) . ') VALUES (' . join(', ', array_map(static fn(string $key) => ':' . $key, $columns)) . ')')->execute($data);
        } catch (Throwable $throwable) {
            file_logger('clickhouse')->error($throwable);
        }

        return false;
    }
}