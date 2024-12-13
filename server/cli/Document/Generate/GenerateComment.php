<?php declare(strict_types=1);

namespace Selpol\Cli\Document\Generate;

readonly class GenerateComment
{
    /**
     * @var string[]
     */
    public array $lines;

    /**
     * @var array<string, string>
     */
    public array $params;

    /**
     * @param string[] $lines
     */
    public function __construct(array $lines, array $params)
    {
        $this->lines = $lines;

        $this->params = $params;
    }

    public function getLine(): string
    {
        return trim(implode(PHP_EOL, $this->lines));
    }
}