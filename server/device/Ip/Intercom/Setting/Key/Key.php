<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Key;

class Key
{
    public function __construct(public string $key, public int $apartment)
    {
    }

    public function equal(Key $other): bool
    {
        return $this->key === $other->key && $this->apartment === $other->apartment;
    }
}