<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Sip;

interface SipInterface
{
    public function getSipStatus(): bool;

    public function getSip(): Sip;

    public function getSipOption(): SipOption;

    public function setSip(Sip $sip): void;

    public function setSipOption(SipOption $sipOption): void;
}