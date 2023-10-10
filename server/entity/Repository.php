<?php declare(strict_types=1);

namespace Selpol\Entity;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Service\Database\Manager;
use Selpol\Service\Database\Page;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Exception\ValidatorException;

/**
 * @template TKey of array-key
 * @template TValue of Entity
 */
abstract class Repository
{
    /**
     * @var class-string<TValue>
     */
    protected string $class;

    protected string $table;

    protected string $id;

    protected bool $audit = false;

    protected array $columns;

    /**
     * @param class-string<TValue> $class
     */
    protected function __construct(string $class)
    {
        $this->class = $class;

        $this->table = $class::$table;

        $this->id = $class::$columnId;

        $this->columns = $class::getColumns();
    }

    /**
     * @psalm-param string $query
     * @psalm-param array $params
     * @psalm-return TValue
     * @throws NotFoundExceptionInterface
     */
    public function fetchRaw(string $query, array $params = []): Entity
    {
        return $this->getManager()->fetchEntity($this->class, $query, $params);
    }

    /**
     * @param string $query
     * @param array $params
     * @return array<TValue>
     * @throws NotFoundExceptionInterface
     */
    public function fetchAllRaw(string $query, array $params = []): array
    {
        return $this->getManager()->fetchAllEntity($this->class, $query, $params);
    }

    /**
     * @param int $page
     * @param int $size
     * @param Criteria|null $criteria
     * @return Page<TValue>
     * @throws NotFoundExceptionInterface
     */
    public function fetchPaginate(int $page, int $size, ?Criteria $criteria = null): Page
    {
        return $this->getManager()->fetchAllEntityPage($this->class, $criteria?->getSqlString() ?? '', $page, $size, $criteria?->getSqlParams() ?? []);
    }

    /**
     * @psalm-param TKey $id
     * @psalm-return TValue
     * @throws NotFoundExceptionInterface
     */
    public function findById(mixed $id): Entity
    {
        return $this->fetchRaw('SELECT * FROM ' . $this->table . ' WHERE ' . $this->id . ' = :' . $this->id, [$this->id => $id]);
    }

    /**
     * @psalm-param TValue $entity
     * @psalm-return bool
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function insert(Entity $entity): bool
    {
        $value = $entity->getValue();
        $columns = [];

        foreach (array_keys($value) as $key)
            if ($key !== $entity::$columnId)
                $columns[$key] = $this->columns[$key];

        $entity->setValue(validator($value, $columns));

        $result = $this->getManager()->insertEntity($entity);

        if ($result) {
            $entity->clearDirty();

            if ($this->audit)
                container(AuditFeature::class)->audit(strval($entity->{$entity::$columnId}), $this->class, 'insert', 'Добавление новой сущности');
        }

        return $result;
    }

    /**
     * @psalm-param TValue $entity
     * @psalm-return bool
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function refresh(Entity $entity): bool
    {
        $entity->{$entity::$columnId} = validate($entity::$columnId, $entity->{$entity::$columnId}, $this->columns[$entity::$columnId]);

        $result = $this->getManager()->refreshEntity($entity);

        if ($result)
            $entity->clearDirty();

        return $result;
    }

    /**
     * @psalm-param TValue $entity
     * @psalm-return bool
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function update(Entity $entity): bool
    {
        if (!$entity->isDirty())
            return true;

        $value = $entity->getDirtyValue();
        $columns = [];

        foreach (array_keys($value) as $key)
            $columns[$key] = $this->columns[$key];

        $entity->setDirtyValue(validator($value, $columns));

        $result = $this->getManager()->updateEntity($entity);

        if ($result) {
            $entity->clearDirty();

            if ($this->audit)
                container(AuditFeature::class)->audit(strval($entity->{$entity::$columnId}), $this->class, 'update', 'Обновление сущности');
        }

        return $result;
    }

    /**
     * @psalm-param TValue $entity
     * @psalm-return bool
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function delete(Entity $entity): bool
    {
        $entity->{$entity::$columnId} = validate($entity::$columnId, $entity->{$entity::$columnId}, $this->columns[$entity::$columnId]);

        $result = $this->getManager()->deleteEntity($entity);

        if ($this->audit && $result)
            container(AuditFeature::class)->audit(strval($entity->{$entity::$columnId}), $this->class, 'update', 'Удаление сущности');

        return $result;
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function insertAndRefresh(Entity $entity): bool
    {
        return $this->insert($entity) && $this->refresh($entity);
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function updateAndRefresh(Entity $entity): bool
    {
        return $this->update($entity) && $this->refresh($entity);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    protected function getManager(): Manager
    {
        return container(DatabaseService::class)->getManager();
    }
}