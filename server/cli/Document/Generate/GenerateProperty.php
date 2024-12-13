<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateProperty
{
    public string $name;
    public string $type;

    public string|bool $document;

    public function __construct(string $name, string $type, string|bool $document)
    {
        $this->name = $name;
        $this->type = $type;

        $this->document = $document;
    }
}