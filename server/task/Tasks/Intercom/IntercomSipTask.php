<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Service\DeviceService;
use Selpol\Service\MqttService;
use Selpol\Task\Task;

class IntercomSipTask extends Task
{
    public function __construct()
    {
        parent::__construct('Формирования списка устройств, без регистрацииа');
    }

    public function onTask(): bool
    {
        $deviceService = container(DeviceService::class);

        $devices = array_map(fn(DeviceIntercom $intercom) => $deviceService->intercomByEntity($intercom), DeviceIntercom::fetchAll());
        $length = count($devices);

        $process = 0.0;
        $step = 100.0 / $length;

        $count = 0;
        $result = [];

        $web = config_get('api.web', 'http://127.0.0.1');

        for ($i = 0; $i < $length; $i++) {
            $device = $devices[$i];

            if ($device == null || $device->model == null || !$device->pingRaw()) {
                $process += $step;

                $this->setProgress($process);

                continue;
            }

            $count++;

            if (!($device instanceof SipInterface)) {
                $process += $step;

                $this->setProgress($process);

                continue;
            }

            $status = $device->getSipStatus();

            if ($status) {
                $process += $step;

                $this->setProgress($process);

                continue;
            }

            $entrances = $devices[$i]->intercom->entrances()->fetchAll(criteria()->limit(1));

            if (count($entrances) == 0) {
                $process += $step;

                $this->setProgress($process);

                continue;
            }

            $houses = $entrances[0]->houses()->fetchAll(criteria()->limit(1));

            if (count($houses) == 0) {
                $process += $step;

                $this->setProgress($process);

                continue;
            }

            $result[] = [
                'ip' => $devices[$i]->intercom->ip,
                'type' => $entrances[0]->entrance_type,
                'model' => $devices[$i]->model->vendor,
                'link' => $web . '/houses/' . $houses[0]->address_house_id . '?houseTab=entrances&entranceId=' . $entrances[0]->house_entrance_id
            ];

            $process += $step;

            $this->setProgress($process);
        }

        if (!$this->sync) {
            container(MqttService::class)->table(
                $this->uuid ?? guid_v4(),
                'Список устройств, без регистрации',
                [
                    'ip' => ['type' => 'text', 'label' => 'Ip-адрес'],
                    'type' => ['type' => 'text', 'label' => 'Тип входа'],
                    'model' => ['type' => 'text', 'label' => 'Модель устройства'],
                    'link' => ['type' => 'link', 'label' => 'Ссылка на устройство']
                ],
                $result,
                $this->uid
            );
        }

        return true;
    }
}