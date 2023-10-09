<?php declare(strict_types=1);

namespace Selpol\Entity;

class Criteria
{
    public static string $PREFIX = 'criteria_';

    private int $limit;
    private int $offset;

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

        if (isset($this->limit))
            $result .= ' LIMIT :' . static::$PREFIX . 'limit';

        if (isset($this->offset))
            $result .= ' OFFSET :' . static::$PREFIX . 'offset';

        return $result;
    }

    public function getSqlParams(): array
    {
        $result = [];

        if (isset($this->limit))
            $result[static::$PREFIX . 'limit'] = $this->limit;

        if (isset($this->offset))
            $result[static::$PREFIX . 'offset'] = $this->offset;

        return $result;
    }
}