<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

use SensitiveParameter;

class Mifare
{
    public function __construct(public bool $enable, #[SensitiveParameter]public string $key, public int $sector)
    {
    }

    public function equal(Mifare $other): bool
    {
        return $this->enable === $other->enable && $this->key === $other->key && $this->sector === $other->sector;
    }
}