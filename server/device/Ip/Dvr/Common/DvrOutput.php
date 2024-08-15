<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrOutput implements JsonSerializable
{
    public function __construct(public DvrContainer $container, public DvrArchive|DvrStreamer|string $value)
    {
    }

    public function jsonSerialize(): array
    {
        return ['container' => $this->container, 'value' => $this->value];
    }
}