<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrOutput implements JsonSerializable
{
    public DvrContainer $container;
    public DvrType $type;

    public DvrArchive|DvrStreamer|string $value;

    public function __construct(DvrContainer $container, DvrType $type, DvrArchive|DvrStreamer|string $value)
    {
        $this->container = $container;
        $this->type = $type;

        $this->value = $value;
    }

    public function jsonSerialize(): array
    {
        return ['container' => $this->container, 'type' => $this->type, 'value' => $this->value];
    }
}