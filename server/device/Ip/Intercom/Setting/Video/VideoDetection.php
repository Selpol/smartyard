<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoDetection
{
    public bool $enable;

    public ?int $left;
    public ?int $top;

    public ?int $width;
    public ?int $height;

    public function __construct(bool $enable, ?int $left, ?int $top, ?int $width, ?int $height)
    {
        $this->enable = $enable;

        $this->left = $left;
        $this->top = $top;

        $this->width = $width;
        $this->height = $height;
    }

    public function equal(VideoDetection $other): bool
    {
        return $this->enable === $other->enable && $this->left === $other->left && $this->top === $other->top && $this->width === $other->width && $this->height === $other->height;
    }
}