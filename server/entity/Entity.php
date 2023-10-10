<?php declare(strict_types=1);

namespace Selpol\Entity;

use JsonSerializable;
use Selpol\Validator\ValidatorItem;

abstract class Entity implements JsonSerializable
{
    private array $value;

    private array $dirty;

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

    public function getDirtyValue(): array
    {
        $result = [];

        if (isset($this->dirty))
            foreach ($this->dirty as $item)
                $result[$item] = $this->value[$item];

        return $result;
    }

    public function setValue(array $value): void
    {
        $this->value = $value;
    }

    public function setDirtyValue(array $value): void
    {
        $this->dirty = array_keys($value);

        foreach ($this->dirty as $item)
            $this->value[$item] = $value[$item];
    }

    public function isDirty(): bool
    {
        return isset($this->dirty) && count($this->dirty) > 0;
    }

    public function __get(string $name)
    {
        return $this->value[$name];
    }

    public function __set(string $name, $value): void
    {
        if (array_key_exists($name, $this->value) && $this->value[$name] === $value)
            return;

        if (!isset($this->dirty))
            $this->dirty = [];

        $this->dirty[] = $name;

        $this->value[$name] = $value;
    }

    public function toArrayMap(array $value): array
    {
        $result = [];

        $keys = array_keys($this->value);

        foreach ($keys as $key)
            if (array_key_exists($key, $value))
                $result[$value[$key]] = $this->value[$key];

        return $result;
    }

    public function clearDirty(): void
    {
        unset($this->dirty);
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