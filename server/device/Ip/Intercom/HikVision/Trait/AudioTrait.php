<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use Selpol\Device\Ip\Intercom\Setting\Audio\AudioLevels;

trait AudioTrait
{
    public function getDefaultAudioLevels(): AudioLevels
    {
        return new AudioLevels([7, 7, 7]);
    }

    public function getAudioLevels(): AudioLevels
    {
        $in = $this->get('/ISAPI/System/Audio/AudioIn/channels/1');
        $out = $this->get('/ISAPI/System/Audio/AudioOut/channels/1');

        return new AudioLevels([
            $in['AudioInVolumelist']['AudioInVlome']['volume'],
            $out['AudioOutVolumelist']['AudioOutVlome']['volume'],
            $out['AudioOutVolumelist']['AudioOutVlome']['talkVolume'],
        ]);
    }

    public function setAudioLevels(AudioLevels $audio): void
    {
        $levels = [
            0 => array_key_exists(0, $audio->value) ? $audio->value[0] : 7,
            1 => array_key_exists(1, $audio->value) ? $audio->value[1] : 7,
            2 => array_key_exists(2, $audio->value) ? $audio->value[2] : 7
        ];

        $this->put('/ISAPI/System/Audio/AudioIn/channels/1', "<AudioIn><id>1</id><AudioInVolumelist><AudioInVlome><type>audioInput</type><volume>$levels[0]</volume></AudioInVlome></AudioInVolumelist></AudioIn>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/Audio/AudioOut/channels/1', "<AudioOut><id>1</id><AudioOutVolumelist><AudioOutVlome><type>audioOutput</type><volume>$levels[1]</volume><talkVolume>$levels[2]</talkVolume></AudioOutVlome></AudioOutVolumelist></AudioOut>", ['Content-Type' => 'application/xml']);
    }
}