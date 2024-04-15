<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Cms;

use Selpol\Device\Ip\Intercom\Setting\Audio\AudioLevels;

class CmsLevels
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