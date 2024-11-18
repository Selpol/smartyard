<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateParameter
{
    public string $name;
    public string $type;

    public GenerateDocument|bool $document;

    public function __construct(string $name, string $type, GenerateDocument|bool $document)
    {
        $this->name = $name;
        $this->type = $type;

        $this->document = $document;
    }
}