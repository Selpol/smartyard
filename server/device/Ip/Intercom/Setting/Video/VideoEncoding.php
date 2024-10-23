<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

class VideoEncoding
{
    public function __construct(public int $primaryBitrate, public int $secondaryBitrate)
    {
    }

    public function equal(VideoEncoding $other): bool
    {
        return $this->primaryBitrate === $other->primaryBitrate && $this->secondaryBitrate === $other->secondaryBitrate;
    }
}