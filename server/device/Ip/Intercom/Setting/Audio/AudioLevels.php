<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Audio;

class AudioLevels
{
    public function __construct(public array $value)
    {
    }

    public function equal(AudioLevels $other): bool
    {
        return $this->value === $other->value;
    }
}