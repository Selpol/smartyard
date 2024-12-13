<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateParameter
{
    public string $name;
    public string $type;

    public GenerateComment|bool $document;

    public function __construct(string $name, string $type, GenerateComment|bool $document)
    {
        $this->name = $name;
        $this->type = $type;

        $this->document = $document;
    }
}