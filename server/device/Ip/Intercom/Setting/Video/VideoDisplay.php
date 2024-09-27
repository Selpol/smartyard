<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoDisplay
{
    public function __construct(public ?string $title)
    {
    }

    public function equal(VideoDisplay $other): bool
    {
        return $this->title === $other->title;
    }
}