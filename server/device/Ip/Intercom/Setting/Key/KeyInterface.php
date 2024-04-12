<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Key;

interface KeyInterface
{
    /**
     * @return Key[]
     */
    public function getKeys(): array;

    /**
     * @param int $apartment
     * @return Key[]
     */
    public function getKeysByApartment(int $apartment): array;
}