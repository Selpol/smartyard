<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioLevels;
use Selpol\Entity\Repository\Core\CoreVarRepository;

trait AudioTrait
{
    public function getDefaultAudioLevels(): AudioLevels
    {
        $coreVar = container(CoreVarRepository::class)->findByName('intercom.is.audio');

        if ($coreVar && $coreVar->var_value) {
            return new AudioLevels(json_decode($coreVar->var_value, true));
        }

        return new AudioLevels([110, 130, 200, 185, 230, 120]);
    }

    public function getAudioLevels(): AudioLevels
    {
        $response = $this->get('/levels');
        $volumes = $response['volumes'];

        return new AudioLevels([$volumes['panelCall'], $volumes['panelTalk'], $volumes['thTalk'], $volumes['thCall'], $volumes['uartFrom'], $volumes['uartTo']]);
    }

    public function setAudioLevels(AudioLevels $audio): void
    {
        if (count($audio->value) !== 6) {
            throw new DeviceException($this, 'Не верные данные аудио');
        }

        $this->put('/levels', ['volumes' => ['panelCall' => $audio->value[0], 'panelTalk' => $audio->value[1], 'thTalk' => $audio->value[2], 'thCall' => $audio->value[3], 'uartFrom' => $audio->value[4], 'uartTo' => $audio->value[5]]]);
        $this->put('/system/settings', ['assist' => ['enable' => false, 'online' => false]]);
    }
}