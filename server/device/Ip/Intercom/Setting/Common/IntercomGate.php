<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class IntercomGate
{
    public array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }

    public function equal(IntercomGate $other): bool
    {
        return $this->value === $other->value;
    }
}