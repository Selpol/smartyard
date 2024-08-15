<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;

readonly class DvrIdentifier implements JsonSerializable
{
    public function __construct(public string $value, public int $start, public int $end, public ?int $subscriber)
    {
    }

    public function isNotExpired(): bool
    {
        return time() >= $this->start && time() <= $this->end;
    }

    public function jsonSerialize(): array
    {
        return ['value' => $this->value, 'start' => $this->start, 'end' => $this->end];
    }
}