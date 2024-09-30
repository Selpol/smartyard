<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use JsonSerializable;

readonly class ConfigValue implements JsonSerializable
{
    public function __construct(
        public string  $default = '',
        public string  $type = 'string',
        public ?string $example = null,
        public ?string $condition = null
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'default' => $this->default,
            'type' => $this->type,
            'example' => $this->example,
            'condition' => $this->condition
        ];
    }
}