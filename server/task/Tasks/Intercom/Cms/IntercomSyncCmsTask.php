<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Cms;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;
use Throwable;

class IntercomSyncCmsTask extends Task implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public int $entranceId;

    public function __construct(int $entranceId)
    {
        parent::__construct('Конфигурация CMS (' . $entranceId . ')');

        $this->entranceId = $entranceId;
    }

    public function onTask(): bool
    {
        $entrance = container(HouseFeature::class)->getEntrance($this->entranceId);

        if (!$entrance || $entrance['shared'])
            return false;

        $this->cms($entrance, $entrance['domophoneId']);

        return true;
    }

    private function cms(array $entrance, int $id): void
    {
        try {
            $device = intercom($id);

            if (!$device->ping())
                throw new DeviceException($device, message: 'Устройство не доступно');

            $cms_allocation = container(HouseFeature::class)->getCms($entrance['entranceId']);

            foreach ($cms_allocation as $item)
                $device->addCmsDeffer($item['cms'] + 1, intval($item['dozen']), intval($item['unit']), intval($item['apartment']));

            $device->deffer();
        } catch (Throwable $throwable) {
            if ($throwable instanceof DeviceException)
                throw $throwable;

            file_logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}