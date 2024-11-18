<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateDocument
{
    /**
     * @var string[]
     */
    public array $lines;

    /**
     * @param string[] $lines
     */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    public function getLine(): string
    {
        return implode(PHP_EOL, $this->lines);
    }
}