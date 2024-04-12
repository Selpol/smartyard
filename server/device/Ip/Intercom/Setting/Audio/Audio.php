<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Audio;

class Audio
{
    public array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }

    public function equal(Audio $other): bool
    {
        return $this->value === $other->value;
    }
}