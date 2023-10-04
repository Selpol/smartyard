<?php declare(strict_types=1);

namespace Selpol\Entity;

class Criteria
{
    public string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}