<?php declare(strict_types=1);

namespace Selpol\Entity;

use JsonSerializable;
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

    public function __set(string $name, $value): void
    {
        $this->value[$name] = $value;
    }

    public function jsonSerialize(): array
    {
        return $this->value;
    }

    /**
     * Список колонок сущности в базе данных
     * @return array<string, array<ValidatorItem>>
     */
    public static abstract function getColumns(): array;
}