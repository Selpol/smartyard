<?php declare(strict_types=1);

namespace Selpol\Feature\Group;

use JsonSerializable;

readonly class GroupPage implements JsonSerializable
{
    private array $data;


    private int $page;
    private int $size;

    public function __construct(array $data, int $page, int $size)
    {
        $this->data = $data;

        $this->page = $page;
        $this->size = $size;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function jsonSerialize(): array
    {
        return ['data' => $this->data, 'page' => $this->page, 'size' => $this->size];
    }
}