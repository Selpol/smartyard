<?php declare(strict_types=1);

namespace Selpol\Feature\File;

class FileInfo
{

    public ?string $filename;

    public ?int $length;

    public ?FileMetadata $metadata;

    public function __construct(?string $filename, ?int $length, ?FileMetadata $metadata)
    {
        $this->filename = $filename;

        $this->length = $length;

        $this->metadata = $metadata;
    }

    public function withFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function withLength(?int $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function withMetadata(?FileMetadata $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function toQuery(): array
    {
        $result = [];

        if ($this->filename) {
            $result['filename'] = $this->filename;
        }

        if ($this->length) {
            $result['length'] = $this->length;
        }

        if ($this->metadata) {
            $result['metadata'] = $this->metadata->toQuery();
        }

        return $result;
    }

    public static function filename(?string $filename): static
    {
        return new FileInfo($filename, null, null);
    }
}
