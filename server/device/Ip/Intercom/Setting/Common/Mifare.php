<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

use SensitiveParameter;

class Mifare
{
    public bool $enable;

    public string $key;
    public int $sector;

    public function __construct(bool $enable, #[SensitiveParameter] string $key, int $sector)
    {
        $this->enable = $enable;

        $this->key = $key;
        $this->sector = $sector;
    }

    public function equal(Mifare $other): bool
    {
        return $this->enable === $other->enable && $this->key === $other->key && $this->sector === $other->sector;
    }
}