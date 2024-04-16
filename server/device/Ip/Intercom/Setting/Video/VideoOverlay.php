<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoOverlay
{
    public ?string $title;

    public function __construct(?string $title)
    {
        $this->title = $title;
    }

    public function equal(VideoOverlay $other): bool
    {
        return $this->title === $other->title;
    }
}