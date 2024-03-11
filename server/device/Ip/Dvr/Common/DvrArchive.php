<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrArchive implements JsonSerializable
{
    public string $src;

    public int $start;
    public int $end;

    public function __construct(string $src, int $start, int $end)
    {
        $this->src = $src;

        $this->start = $start;
        $this->end = $end;
    }

    public function jsonSerialize(): array
    {
        return ['src' => $this->src, 'start' => $this->start, 'end' => $this->end];
    }
}