<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Gms;

interface GmsInterface
{
    public function addPhone(string $phone): void;

    public function removePhone(string $phone): void;
}