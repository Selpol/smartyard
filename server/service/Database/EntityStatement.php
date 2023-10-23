<?php declare(strict_types=1);

namespace Selpol\Service\Database;

use PDO;
use PDOStatement;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Framework\Entity\EntityMessage;

readonly class EntityStatement implements EntityStatementInterface
{
    private PDOStatement $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function execute(?array $value = null): bool
    {
        return $this->statement->execute($value);
    }

    public function fetch(): ?array
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchColumn(int $index): mixed
    {
        return $this->statement->fetchColumn($index);
    }

    public function fetchAll(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(): int
    {
        return $this->statement->rowCount();
    }

    public function error(): array
    {
        $result = [];

        foreach ($this->statement->errorInfo() as $error)
            $result[] = new EntityMessage($error[1], $error[2]);

        return $result;
    }
}