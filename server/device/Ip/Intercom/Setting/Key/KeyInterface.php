<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Key;

interface KeyInterface
{
    /**
     * @param int $apartment
     * @return Key[]
     * @return Key[]
     */
    public function getKeys(int $apartment): array;

    public function addKey(Key $key): void;

    public function removeKey(Key|string $key): void;

    public function clearKey(): void;
}