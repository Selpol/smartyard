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
        if ($value)
            foreach ($value as $key => $item) {
                if (is_bool($item)) $this->statement->bindValue($key, $item, PDO::PARAM_BOOL);
                else if (is_int($item)) $this->statement->bindValue($key, $item, PDO::PARAM_INT);
                else $this->statement->bindValue($key, $item);
            }

        return $this->statement->execute();
    }

    public function fetch(): ?array
    {
        $result = $this->statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    public function fetchColumn(int $index): mixed
    {
        return $this->statement->fetchColumn($index);
    }

    public function fetchAll(): array
    {
        $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);

        return $result === false ? [] : $result;
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