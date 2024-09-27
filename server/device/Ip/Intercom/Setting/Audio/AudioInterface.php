<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Audio;

interface AudioInterface
{
    public function getDefaultAudioLevels(): AudioLevels;

    public function getAudioLevels(): AudioLevels;

    public function setAudioLevels(AudioLevels $audio): void;
}