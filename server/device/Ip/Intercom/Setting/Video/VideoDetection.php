<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoDetection
{
    public bool $enable;

    public function __construct(bool $enable)
    {
        $this->enable = $enable;
    }

    public function equal(VideoDetection $other): bool
    {
        return $this->enable === $other->enable;
    }
}