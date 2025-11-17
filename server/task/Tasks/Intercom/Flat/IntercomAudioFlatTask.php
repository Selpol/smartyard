<?php

namespace Selpol\Task\Tasks\Intercom\Flat;

use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Task\Task;

class IntercomAudioFlatTask extends Task
{
    public function __construct(public int $id, public int $flat)
    {
        parent::__construct('Синхронизация аудио квартиры (' . $id . ', ' . $this->flat . ')');

        $this->setLogger(file_logger('task-intercom'));
    }

    public function onTask(): bool
    {
        $intercom = intercom($this->id);

        if ($intercom instanceof ApartmentInterface) {
            $levels = explode(',', $intercom->resolver->string(ConfigKey::AudioVolume->with_end($this->flat), ''));

            if (count($levels) == 6) {
                $intercom->setApartmentAudio($this->flat, $levels);
            }
        }

        return false;
    }
}