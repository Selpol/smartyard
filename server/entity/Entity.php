<?php declare(strict_types=1);

namespace Selpol\Entity;

use JsonSerializable;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Exception\ValidatorException;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorItem;

abstract class Entity implements JsonSerializable
{
    private array $value;

    /**
     * Таблица сущности
     * @var string
     */
    public static string $table;

    /**
     * Идентификатор сущности в базе данных
     * @var string
     */
    public static string $columnId = 'id';

    /**
     * Стратегия получения идентификатора сущности
     * @var string
     */
    public static string $columnIdStrategy = 'serial';

    /**
     * Колонка создание сущности
     * @var string|null
     */
    public static ?string $columnCreate = null;

    /**
     * Колонка последнего обновления сущности
     * @var string|null
     */
    public static ?string $columnUpdate = null;

    /**
     * Валидировать ли колонки, перед их установкой в сущность
     * @var bool
     */
    protected static bool $validateSet = true;

    private static ?array $columns = null;

    public function __construct(array $value = [])
    {
        $this->value = $value;
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(array $value): void
    {
        $this->value = $value;
    }

    public function __get(string $name)
    {
        return $this->value[$name];
    }

    /**
     * @throws ValidatorException
     */
    public function __set(string $name, $value): void
    {
        if (static::$validateSet) {
            $this->value[$name] = validate($name, $value, $this->getCacheColumns()[$name]);
        } else $this->value[$name] = $value;
    }

    /**
     * @throws ValidatorException
     */
    public function validateId(): mixed
    {
        return validate(static::$columnId, array_key_exists(static::$columnId, $this->value) ? $this->value[static::$columnId] : null, static::getCacheColumns()[static::$columnId]);
    }

    /**
     * @throws ValidatorException
     */
    public function validateBody(): array
    {
        $columns = static::getCacheColumns();

        if (array_key_exists(static::$columnId, $columns))
            unset($columns[static::$columnId]);

        $validator = new Validator($this->value, $columns);

        return $validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function validateExistBody(): array
    {
        $columns = [];
        $cacheColumns = static::getCacheColumns();

        foreach (array_keys($this->value) as $key)
            if ($key !== static::$columnId)
                $columns[$key] = $cacheColumns[$key];

        $validator = new Validator($this->value, $columns);

        return $validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function validate(): array
    {
        $validator = new Validator($this->value, static::getCacheColumns());

        return $validator->validate();
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function refresh(): bool
    {
        return container(DatabaseService::class)->getManager()->refreshEntity($this);
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function insert(): bool
    {
        return container(DatabaseService::class)->getManager()->insertEntity($this);
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function update(): bool
    {
        return container(DatabaseService::class)->getManager()->updateEntity($this);
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function delete(): bool
    {
        return container(DatabaseService::class)->getManager()->deleteEntity($this);
    }

    public function jsonSerialize(): array
    {
        return $this->value;
    }

    public static function getColumnsKeys(): array
    {
        return array_keys(static::getCacheColumns());
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public static function fetch(string $query, array $params = []): static
    {
        return container(DatabaseService::class)->getManager()->fetchEntity(static::class, $query, $params);
    }

    /**
     * @return array<static>
     * @throws NotFoundExceptionInterface
     */
    public static function fetchAll(string $query, array $params = []): array
    {
        return container(DatabaseService::class)->getManager()->fetchAllEntity(static::class, $query, $params);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public static function fetchById(mixed $value): static
    {
        return container(DatabaseService::class)->getManager()->fetchEntity(static::class, 'SELECT ' . implode(', ', static::getColumnsKeys()) . ' FROM ' . static::$table . ' WHERE ' . static::$columnId . ' = :' . static::$columnId, [static::$columnId => $value]);
    }

    /**
     * Список колонок сущности в базе данных
     * @return array<string, array<ValidatorItem>>
     */
    protected static abstract function getColumns(): array;

    /**
     * @return array<string, array<ValidatorItem>>
     */
    private static function getCacheColumns(): array
    {
        if (static::$columns === null)
            static::$columns = static::getColumns();

        return static::$columns;
    }
}