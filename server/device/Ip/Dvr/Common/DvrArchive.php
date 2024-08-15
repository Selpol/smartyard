<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrArchive implements JsonSerializable
{
    public function __construct(public DvrStreamer|string $src, public int $start, public int $end, public int $seek, public ?string $timezone, public ?string $token)
    {
    }

    public function jsonSerialize(): array
    {
        return ['src' => $this->src, 'start' => $this->start, 'end' => $this->end, 'seek' => $this->seek, 'timezone' => $this->timezone, 'token' => $this->token];
    }
}