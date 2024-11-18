<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateClass
{
    public string $name;
    public string $class;

    public string $path;

    /**
     * @var GenerateMethod[]
     */
    public array $methods;

    public GenerateComment|bool $comment;

    public function __construct(string $name, string $class, string $path, array $methods, GenerateComment|bool $comment)
    {
        $this->name = $name;
        $this->class = $class;

        $this->path = $path;

        $this->methods = $methods;

        $this->comment = $comment;
    }
}