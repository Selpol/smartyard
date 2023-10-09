<?php declare(strict_types=1);

namespace Selpol\Service\Database;

use JsonSerializable;
use Selpol\Entity\Entity;

/**
 * @template T of Entity
 */
class Page implements JsonSerializable
{
    /** @var T[] */
    private array $data;

    private int $total;

    private int $page;
    private int $size;

    /**
     * @param T[] $data
     */
    public function __construct(array $data, int $total, int $page, int $size)
    {
        $this->data = $data;

        $this->total = $total;
        $this->page = $page;
        $this->size = $size;
    }

    /**
     * @return T[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getTotal(): int
    {
        return $this->total;
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
        return ['data' => $this->data, 'total' => $this->total, 'page' => $this->page, 'size' => $this->size];
    }
}