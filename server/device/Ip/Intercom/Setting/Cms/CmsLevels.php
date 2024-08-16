<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Cms;

class CmsLevels
{
    public function __construct(public array $value)
    {
    }

    public function equal(CmsLevels $other): bool
    {
        return $this->value === $other->value;
    }
}