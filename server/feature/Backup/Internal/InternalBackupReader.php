<?php declare(strict_types=1);

namespace Selpol\Feature\Backup\Internal;

use Psr\Http\Message\StreamInterface;

readonly class InternalBackupReader
{
    private StreamInterface $stream;

    public function __construct(string $path)
    {
        $this->stream = stream(fopen($path, 'r'));
    }

    public function section(): array|bool
    {
        if ($this->readLine() == InternalBackupWriter::SECTION) {
            $section = $this->readLine();

            $segments = explode(' - ', $section, 3);

            if ($segments[0] === 'TABLE')
                return ['TABLE', $segments[1], explode(', ', $segments[2])];
            else if ($segments[0] === 'SEQUENCE')
                return ['SEQUENCE', $segments[1], intval($segments[2])];
        }

        return false;
    }

    public function row(): array|bool
    {
        $line = $this->readLine();

        if ($line) {
            if ($line === InternalBackupWriter::SECTION)
                return false;

            return json_decode($line, true);
        }

        return false;
    }

    private function readLine(): string|bool
    {
        $result = '';

        while (!$this->stream->eof()) {
            $char = $this->stream->read(1);

            if ($char === PHP_EOL) return $result;
            else $result .= $char;
        }

        return false;
    }
}