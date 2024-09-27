<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use JsonSerializable;

readonly class ConfigItem implements JsonSerializable
{
    public function __construct(
        public string  $key,
        public string  $title,
        public string  $default,
        public ?string $global,
        /** @var array<string> $keys */
        public array   $keys
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'default' => $this->default,
            'global' => $this->global,
            'keys' => $this->keys
        ];
    }
}