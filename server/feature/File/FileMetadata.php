<?php declare(strict_types=1);

namespace Selpol\Feature\File;

class FileMetadata
{
    public ?string $contentType;

    public ?int $subscirberId;
    public ?int $cameraId;
    public ?int $faceId;

    public ?int $start;
    public ?int $end;

    public ?int $expire;

    public function __construct(?string $contentType, ?int $subscirberId, ?int $cameraId, ?int $faceId, ?int $start, ?int $end, ?int $expire)
    {
        $this->contentType = $contentType;

        $this->subscirberId = $subscirberId;
        $this->cameraId = $cameraId;
        $this->faceId = $faceId;

        $this->start = $start;
        $this->end = $end;

        $this->expire = $expire;
    }

    public function withContentType(?string $contentType): static
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function withSubscirberId(?int $subscirberId): static
    {
        $this->subscirberId = $subscirberId;

        return $this;
    }

    public function withCameraId(?int $cameraId): static
    {
        $this->cameraId = $cameraId;

        return $this;
    }

    public function withFaceId(?int $faceId): static
    {
        $this->faceId = $faceId;

        return $this;
    }

    public function withStart(?int $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function withEnd(?int $end): static
    {
        $this->end = $end;

        return $this;
    }

    public function withExpire(?int $expire): static
    {
        $this->expire = $expire;

        return $this;
    }

    public static function contentType(?string $contentType): FileMetadata
    {
        return new FileMetadata($contentType, null, null, null, null, null, null);
    }
}
