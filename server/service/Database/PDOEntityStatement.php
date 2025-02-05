<?php declare(strict_types=1);

namespace Selpol\Service\Database;

use PDO;
use PDOException;
use PDOStatement;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Framework\Entity\EntityMessage;
use Selpol\Framework\Entity\Exception\EntityException;

class PDOEntityStatement implements EntityStatementInterface
{
    private array $values = [];

    public function __construct(private readonly PDOStatement $statement)
    {
    }

    public function execute(?array $value = null): bool
    {
        $values = $value !== null && $value !== [] ? array_merge($this->values, $value) : $this->values;

        if ($values !== []) {
            foreach ($values as $key => $item) {
                if (is_array($item)) {
                    if ($item[0] === 0) {
                        $this->statement->bindValue($key, $item[1], PDO::PARAM_STMT);

                        continue;
                    }

                    $item = $item[1];
                }

                if (is_bool($item)) {
                    $this->statement->bindValue($key, $item, PDO::PARAM_BOOL);
                } elseif (is_int($item)) {
                    $this->statement->bindValue($key, $item, PDO::PARAM_INT);
                } else {
                    $this->statement->bindValue($key, $item);
                }
            }
        }

        try {
            return $this->statement->execute();
        } catch (PDOException $pdoException) {
            if ($pdoException->getCode() == 23505) {
                throw new EntityException([new EntityMessage(23505, $pdoException->getMessage())], 'Повторяющийся идентификатор', $pdoException->getMessage(), 400);
            }

            if ($pdoException->getCode() == 23503) {
                throw new EntityException([new EntityMessage(23503, $pdoException->getMessage())], 'Существуют дочерние зависимости', $pdoException->getMessage(), 400);
            }

            return false;
        }
    }

    public function bind(string $key, float|bool|int|string $value): static
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function bindRaw(string $key, string $value): static
    {
        $this->values[$key] = [0, $value];

        return $this;
    }

    public function fetch(int $flags = self::FETCH_ASSOC): ?array
    {
        $result = $this->statement->fetch($this->flags($flags));

        return $result === false ? null : $result;
    }

    public function fetchColumn(int $index): mixed
    {
        return $this->statement->fetchColumn($index);
    }

    public function fetchAll(int $flags = self::FETCH_ASSOC): array
    {
        $result = $this->statement->fetchAll($this->flags($flags));

        return $result === false ? [] : $result;
    }

    public function count(): int
    {
        return $this->statement->rowCount();
    }

    public function error(): array
    {
        $result = [];

        foreach ($this->statement->errorInfo() as $error) {
            $result[] = new EntityMessage($error[1], $error[2]);
        }

        return $result;
    }

    private function flags(int $flags): int
    {
        if ($flags == self::FETCH_ASSOC) {
            return PDO::FETCH_ASSOC;
        } else if ($flags == self::FETCH_NUMBER) {
            return PDO::FETCH_NUM;
        }

        return -1;
    }
}