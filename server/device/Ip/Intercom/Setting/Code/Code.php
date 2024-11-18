<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Code;

class Code
{
    public function __construct(public string|int $code, public int $apartment)
    {
    }

    public function equal(Code $other): bool
    {
        return $this->code === $other->code && $this->apartment === $other->apartment;
    }
}