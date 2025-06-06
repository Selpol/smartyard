<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateMethod
{
    public string $name;

    public string $method;

    public string $path;

    public string $scope;

    /**
     * @var GenerateParameter[]
     */
    public array $parameters;

    public GenerateComment|bool $comment;

    public function __construct(string $name, string $method, string $path, string $scope, array $parameters, GenerateComment|bool $comment)
    {
        $this->name = $name;

        $this->method = $method;

        $this->path = $path;

        $this->scope = $scope;

        $this->parameters = $parameters;

        $this->comment = $comment;
    }
}