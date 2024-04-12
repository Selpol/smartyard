<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Mifare
{
    public string $key;
    public int $sector;

    public function __construct(string $key, int $sector)
    {
        $this->key = $key;
        $this->sector = $sector;
    }

    public function equal(Mifare $other): bool
    {
        return $this->key === $other->key && $this->sector === $other->sector;
    }
}