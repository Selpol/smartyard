<?php declare(strict_types=1);

namespace Selpol\Feature\Group;

use ArrayAccess;
use JsonSerializable;
use Selpol\Framework\Entity\Entity;

/**
 * @template V of Entity
 * @template F of Entity
 * @template T
 *
 * @property string $name
 *
 * @property class-string<V> $type
 * @property class-string<F> $for
 *
 * @property T $id
 *
 * @property V[] $value
 */
class Group implements ArrayAccess, JsonSerializable
{
    private array $value;

    public function __construct(array $value)
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

    /**
     * @psalm-return V
     */
    public function getForEntity(): Entity
    {
        return $this->for::findById($this->id);
    }

    /**
     * @psalm-return iterable<V>
     */
    public function getValueEntities(): iterable
    {
        foreach ($this->value as $value)
            yield $this->type::findById($value);
    }

    public function jsonSerialize(): array
    {
        return $this->value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->value);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }
}