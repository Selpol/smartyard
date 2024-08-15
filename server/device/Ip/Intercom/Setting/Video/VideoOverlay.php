<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoOverlay
{
    public function __construct(public ?string $title)
    {
    }

    public function equal(VideoOverlay $other): bool
    {
        return $this->title === $other->title;
    }
}