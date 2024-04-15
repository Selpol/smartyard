<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Code;

class Code
{
    public int $code;

    public int $apartment;

    public function __construct(int $code, int $apartment)
    {
        $this->code = $code;

        $this->apartment = $apartment;
    }

    public function equal(Code $other): bool
    {
        return $this->code === $other->code && $this->apartment === $other->apartment;
    }
}