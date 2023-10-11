<?php declare(strict_types=1);

namespace Selpol\Entity;

class Criteria
{
    public static string $PREFIX = 'criteria_';

    private array $params;

    private array $conditions;

    private array $order;

    private int $limit;
    private int $offset;

    public function bind(string $column, mixed $value): static
    {
        if (!isset($this->params))
            $this->params = [];

        $this->params[$column] = $value;

        return $this;
    }

    public function where(string $condition): static
    {
        return $this->addCondition('AND', $condition, null, null);
    }

    public function orWhere(string $condition): static
    {
        return $this->addCondition('OR', $condition, null, null);
    }

    public function simple(string $column, string $operator, mixed $value): static
    {
        return $this->addCondition('AND', $column, $operator, $value);
    }

    public function orSimple(string $column, string $operator, mixed $value): static
    {
        return $this->addCondition('OR', $column, $operator, $value);
    }

    public function between(string $column, mixed $left, mixed $right): static
    {
        return $this->addCondition('AND', $column, 'BETWEEN', [$left, $right]);
    }

    public function orBetween(string $column, mixed $left, mixed $right): static
    {
        return $this->addCondition('OR', $column, 'BETWEEN', [$left, $right]);
    }

    public function like(string $column, string $value): static
    {
        return $this->addCondition('AND', $column, 'LIKE', $value);
    }

    public function orLike(string $column, string $value): static
    {
        return $this->addCondition('OR', $column, 'LIKE', $value);
    }

    public function order(string $column, string $value): static
    {
        if (!isset($this->order))
            $this->order = [];

        $this->order[] = ['column' => $column, 'value' => $value];

        return $this;
    }

    public function asc(string $column): static
    {
        return $this->order($column, 'ASC');
    }

    public function desc(string $column): static
    {
        return $this->order($column, 'DESC');
    }

    public function limit(int $value): static
    {
        $this->limit = $value;

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offset = $value;

        return $this;
    }

    public function page(int $value, int $size = 10): static
    {
        return $this->limit($size)->offset($value * $size);
    }

    public function getSqlString(): string
    {
        $result = '';

        if (isset($this->conditions)) {
            $result .= ' WHERE';

            foreach ($this->conditions as $condition) {
                if ($condition['operator'] !== 'BETWEEN')
                    $result .= ' ' . $condition['column'] . ' ' . $condition['operator'] . ' :' . static::$PREFIX . $condition['column'];
                else
                    $result .= ' ' . $condition['column'] . ' BETWEEN ' . static::$PREFIX . $condition['column'] . '_left AND ' . static::$PREFIX . $condition['column'] . '_right';
            }
        }

        if (isset($this->order)) {
            $result .= ' ORDER BY';

            foreach ($this->order as $item) {
                $result .= ' ' . $item['column'] . ' ' . $item['value'];
            }
        }

        if (isset($this->limit))
            $result .= ' LIMIT :' . static::$PREFIX . 'limit';

        if (isset($this->offset))
            $result .= ' OFFSET :' . static::$PREFIX . 'offset';

        return $result;
    }

    public function getSqlParams(): array
    {
        $result = $this->params ?? [];

        if (isset($this->conditions))
            foreach ($this->conditions as $condition) {
                if (array_key_exists('value', $condition)) {
                    if ($condition['operator'] !== 'BETWEEN')
                        $result[static::$PREFIX . $condition['column']] = $condition['value'];
                    else {
                        $result[static::$PREFIX . $condition['column'] . '_left'] = $condition['value'][0];
                        $result[static::$PREFIX . $condition['column'] . '_right'] = $condition['value'][1];
                    }
                }
            }

        if (isset($this->limit))
            $result[static::$PREFIX . 'limit'] = $this->limit;

        if (isset($this->offset))
            $result[static::$PREFIX . 'offset'] = $this->offset;

        return $result;
    }

    private function addCondition(string $type, string $column, ?string $operator, mixed $value): static
    {
        if (!isset($this->conditions))
            $this->conditions = [];

        $condition = ['type' => $type, 'column' => $column];

        if ($operator !== null) {
            $condition['operator'] = $operator;
            $condition['value'] = $value;
        }

        $this->conditions[] = $condition;

        return $this;
    }
}