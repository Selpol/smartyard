<?php declare(strict_types=1);

namespace Selpol\Feature\File;

use Psr\Http\Message\StreamInterface;
use Selpol\Feature\File\FileInfo;

class File
{
    public readonly StreamInterface $stream;

    public FileInfo $info;

    public function __construct(StreamInterface $stream, FileInfo $info)
    {
        $this->stream = $stream;

        $this->info = $info;
    }

    public function contents(): string
    {
        return $this->stream->getContents();
    }

    public function withInfo(FileInfo $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function withFilename(?string $filename): static
    {
        $this->info->filename = $filename;

        return $this;
    }

    public function withLength(?int $length): static
    {
        $this->info->length = $length;

        return $this;
    }

    public function withMetadata(?FileMetadata $metadata): static
    {
        $this->info->metadata = $metadata;

        return $this;
    }

    public static function stream(StreamInterface $stream, FileInfo $info = new FileInfo(null, null, null)): File
    {
        return new File($stream, $info);
    }
}