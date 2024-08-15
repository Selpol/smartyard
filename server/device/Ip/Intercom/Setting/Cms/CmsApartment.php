<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Cms;

class CmsApartment
{
    public function __construct(public int $index, public int $dozen, public int $unit, public int $apartment)
    {
    }

    public function equal(CmsApartment $other): bool
    {
        return $this->index === $other->index && $this->dozen === $other->dozen && $this->unit === $other->unit && $this->apartment === $other->apartment;
    }
}