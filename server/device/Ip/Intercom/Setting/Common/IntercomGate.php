<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class IntercomGate
{
    public function __construct(public array $value)
    {
    }

    public function equal(IntercomGate $other): bool
    {
        return $this->value === $other->value;
    }
}