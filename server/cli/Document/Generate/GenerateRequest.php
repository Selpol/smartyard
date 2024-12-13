<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateRequest
{
    public string $type;

    /**
     * @var GenerateProperty[]
     */
    public array $properties;

    public function __construct(string $type, array $properties)
    {
        $this->type = $type;

        $this->properties = $properties;
    }
}