<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Audio;

interface AudioInterface
{
    public function getAudio(): Audio;

    public function setAudio(Audio $audio): void;
}