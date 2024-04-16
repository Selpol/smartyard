<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoDisplay
{
    public ?string $title;

    public function __construct(?string $title)
    {
        $this->title = $title;
    }

    public function equal(VideoDisplay $other): bool
    {
        return $this->title === $other->title;
    }
}