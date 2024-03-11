<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrArchive implements JsonSerializable
{
    public string $src;

    public int $start;
    public int $end;

    public int $seek;

    public function __construct(string $src, int $start, int $end, int $seek)
    {
        $this->src = $src;

        $this->start = $start;
        $this->end = $end;

        $this->seek = $seek;
    }

    public function jsonSerialize(): array
    {
        return ['src' => $this->src, 'start' => $this->start, 'end' => $this->end];
    }
}