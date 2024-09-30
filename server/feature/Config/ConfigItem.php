<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use JsonSerializable;

readonly class ConfigItem implements JsonSerializable
{
    public function __construct(
        public string      $key,
        public string      $title,
        public ?string     $global,
        public ConfigValue $value,
        /** @var array<string> $keys */
        public array       $keys
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'global' => $this->global,
            'value' => $this->value,
            'keys' => $this->keys
        ];
    }
}