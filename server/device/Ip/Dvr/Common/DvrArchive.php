<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrArchive implements JsonSerializable
{
    public DvrStreamer|string $src;

    public int $start;
    public int $end;

    public int $seek;

    public ?string $token;

    public function __construct(DvrStreamer|string $src, int $start, int $end, int $seek, ?string $token)
    {
        $this->src = $src;

        $this->start = $start;
        $this->end = $end;

        $this->seek = $seek;

        $this->token = $token;
    }

    public function jsonSerialize(): array
    {
        return ['src' => $this->src, 'start' => $this->start, 'end' => $this->end, 'seek' => $this->seek, 'token' => $this->token];
    }
}