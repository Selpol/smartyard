<?php declare(strict_types=1);

namespace Selpol\Service\Database;

use PDO;
use Selpol\Framework\Entity\Database\EntityConnectionInterface;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Service\DatabaseService;

readonly class EntityConnection implements EntityConnectionInterface
{
    public function statement(string $value): EntityStatementInterface
    {
        return new EntityStatement($this->getConnection()->prepare($value));
    }

    public function lastInsertId(string $value): mixed
    {
        return $this->getConnection()->lastInsertId($value);
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    private function getConnection(): ?PDO
    {
        return container(DatabaseService::class)->getConnection();
    }
}