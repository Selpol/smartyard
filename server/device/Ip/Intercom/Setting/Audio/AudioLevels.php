<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Audio;

class AudioLevels
{
    public array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }

    public function equal(AudioLevels $other): bool
    {
        return $this->value === $other->value;
    }
}