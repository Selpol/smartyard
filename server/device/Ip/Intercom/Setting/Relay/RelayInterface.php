<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Relay;

interface RelayInterface
{
    public function getRelay(): Relay;

    public function setRelay(Relay $relay): void;
}