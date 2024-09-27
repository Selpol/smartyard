<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Gate
{
    public function __construct(public string $address, public int $prefix, public int $begin, public int $end)
    {
    }

    public function equal(Gate $other): bool
    {
        return $this->address === $other->address && $this->prefix === $other->prefix && $this->begin === $other->begin && $this->end === $other->end;
    }
}