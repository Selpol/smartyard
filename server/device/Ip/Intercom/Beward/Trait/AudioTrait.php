<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Audio\AudioLevels;
use Throwable;

trait AudioTrait
{
    public function getDefaultAudioLevels(): AudioLevels
    {
        return new AudioLevels(array_map('intval', explode(',', $this->resolver->string('audio.volume', ''))));
    }

    public function getAudioLevels(): AudioLevels
    {
        try {
            $response = $this->parseParamValueHelp($this->get('/cgi-bin/audio_cgi', ['action' => 'get'], parse: false));

            return new AudioLevels([
                0 => $response['AudioInVol'],
                1 => $response['AudioOutVol'],
                2 => $response['SystemVol'],
                3 => $response['AHSVol'],
                4 => $response['AHSSens'],
                5 => $response['GateInVol'],
                6 => $response['GateOutVol'],
                7 => $response['GateAHSVol'],
                8 => $response['GateAHSSens'],
                9 => $response['MicInSensitivity'],
                10 => $response['MicOutSensitivity'],
                11 => $response['SpeakerInVolume'],
                12 => $response['SpeakerOutVolume'],
                13 => $response['KmnMicInSensitivity'],
                14 => $response['KmnMicOutSensitivity'],
                15 => $response['KmnSpeakerInVolume'],
                16 => $response['KmnSpeakerOutVolume'],
            ]);
        } catch (Throwable) {
            return new AudioLevels([]);
        }
    }

    public function setAudioLevels(AudioLevels $audio): void
    {
        $this->post('/cgi-bin/audio_cgi', [
            'action' => 'set',
            'AudioInVol' => @$audio->value[0],
            'AudioOutVol' => @$audio->value[1],
            'SystemVol' => @$audio->value[2],
            'AHSVol' => @$audio->value[3],
            'AHSSens' => @$audio->value[4],
            'GateInVol' => @$audio->value[5],
            'GateOutVol' => @$audio->value[6],
            'GateAHSVol' => @$audio->value[7],
            'GateAHSSens' => @$audio->value[8],
            'MicInSensitivity' => @$audio->value[9],
            'MicOutSensitivity' => @$audio->value[10],
            'SpeakerInVolume' => @$audio->value[11],
            'SpeakerOutVolume' => @$audio->value[12],
            'KmnMicInSensitivity' => @$audio->value[13],
            'KmnMicOutSensitivity' => @$audio->value[14],
            'KmnSpeakerInVolume' => @$audio->value[15],
            'KmnSpeakerOutVolume' => @$audio->value[16],
        ]);
    }
}