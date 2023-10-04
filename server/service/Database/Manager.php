<?php declare(strict_types=1);

namespace Selpol\Service\Database;

use PDO;
use Selpol\Entity\Entity;
use Selpol\Entity\Exception\EntityException;

class Manager
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @template T of Entity
     * @psalm-param class-string<T> $class
     * @psalm-param string $query
     * @psalm-param array $params
     * @psalm-return T
     * @throws EntityException
     */
    public function fetchEntity(string $class, string $query, array $params = []): Entity
    {
        if (!class_exists($class) | !is_subclass_of($class, Entity::class))
            throw new EntityException('Сущности не существует', 500);

        $statement = $this->connection->prepare($query);

        if (!$statement || !$statement->execute($params))
            throw new EntityException('Ошибка поиска сущности', 500);

        $value = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$value)
            throw new EntityException('Сущность не найдена', 404);

        return new $class($value);
    }

    /**
     * @template T of Entity
     * @psalm-param class-string<T> $class
     * @psalm-param string $query
     * @psalm-param array $params
     * @psalm-return array<T>
     * @throws EntityException
     */
    public function fetchAllEntity(string $class, string $query, array $params = []): ?array
    {
        if (!class_exists($class) | !is_subclass_of($class, Entity::class))
            throw new EntityException('Сущности не существует', 500);

        $statement = $this->connection->prepare($query);

        if (!$statement || !$statement->execute($params))
            throw new EntityException('Ошибка поиска сущности', 500);

        $value = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$value)
            return [];

        return array_map(static fn(array $item) => new $class($item), $value);
    }

    public function refreshEntity(Entity $entity): bool
    {
        $value = $entity->getValue();
        $id = $value[$entity::$columnId];

        $statement = $this->connection->prepare('SELECT * FROM ' . $entity::$table . ' WHERE ' . $entity::$columnId . ' = :' . $entity::$columnId);

        $result = $statement->execute([$entity::$columnId => $id]);

        if ($result) {
            $entity->setValue($statement->fetch(PDO::FETCH_ASSOC));

            return true;
        }

        return false;
    }

    public function insertEntity(Entity $entity): bool
    {
        $value = $entity->getValue();
        $id = $this->getEntityId($entity);

        if (array_key_exists($entity::$columnId, $value))
            unset($value[$entity::$columnId]);

        $insertColumn = implode(', ', array_keys($value));
        $valuesColumnValue = implode(', ', array_map(static fn(string $key) => ':' . $key, array_keys($value)));

        if ($id !== null) {
            $insertColumn = $entity::$columnId . ', ' . $insertColumn;
            $valuesColumnValue = ':' . $entity::$columnId . ', ' . $valuesColumnValue;

            $value = array_merge([$entity::$columnId => $id], $value);
        }

        $result = $this->connection->prepare('INSERT INTO ' . $entity::$table . '(' . $insertColumn . ') VALUES(' . $valuesColumnValue . ')')->execute($value);

        if ($result) {
            if ($id === null)
                $id = $this->connection->lastInsertId($entity::$table . '_' . $entity::$columnId . '_seq');

            $entity->{$entity::$columnId} = $id;
        }

        return $result;
    }

    public function updateEntity(Entity $entity): bool
    {
        $value = $entity->getValue();
        $id = $value[$entity::$columnId];

        unset($value[$entity::$columnId]);

        if ($entity::$columnCreate && array_key_exists($entity::$columnCreate, $value))
            unset($value[$entity::$columnCreate]);

        if ($entity::$columnUpdate && array_key_exists($entity::$columnUpdate, $value))
            unset($value[$entity::$columnUpdate]);

        $updateColumnValue = implode(', ', array_map(static fn(string $key) => $key . ' = :' . $key, array_keys($value)));

        if ($entity::$columnUpdate)
            $updateColumnValue .= ', ' . $entity::$columnUpdate . ' = NOW()';

        return $this->connection->prepare('UPDATE ' . $entity::$table . ' SET ' . $updateColumnValue . ' WHERE ' . $entity::$columnId . ' = :' . $entity::$columnId)->execute(array_merge([$entity::$columnId => $id], $value));
    }

    public function deleteEntity(Entity $entity): bool
    {
        $value = $entity->getValue();
        $id = $value[$entity::$columnId];

        return $this->connection->prepare('DELETE FROM ' . $entity::$table . ' WHERE ' . $entity::$columnId . ' = :id')->execute(['id' => $id]);
    }

    /**
     * @param Entity $entity
     * @return mixed
     * @throws EntityException
     */
    private function getEntityId(Entity $entity): mixed
    {
        if ($entity::$columnIdStrategy === 'serial')
            return null;
        else if (str_ends_with($entity::$columnIdStrategy, '_seq')) {
            $statement = $this->connection->prepare('SELECT NEXTVAL(\'' . $entity::$columnIdStrategy . '\')');

            if ($statement->execute())
                return $statement->fetchColumn();

            throw new EntityException('Неудалось получить идентификатор сущности');
        }

        throw new EntityException('Неизвестный тип идентификатора');
    }
}