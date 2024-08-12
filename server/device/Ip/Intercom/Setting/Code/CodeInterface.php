<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Code;

interface CodeInterface
{
    /**
     * @param int|null $apartment
     * @return Code[]
     */
    public function getCodes(?int $apartment): array;

    public function addCode(Code $code): void;

    public function removeCode(Code $code): void;

    public function clearCode(): void;
}