<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom\Cms;

use RuntimeException;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;
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

        $this->setLogger(file_logger('task-intercom'));
    }

    public function onTask(): bool
    {
        $entrance = container(HouseFeature::class)->getEntrance($this->entranceId);

        if (!$entrance || $entrance['shared']) {
            return false;
        }

        $this->cms($entrance, $entrance['domophoneId']);

        return true;
    }

    private function cms(array $entrance, int $id): void
    {
        try {
            $intercom = intercom($id);

            if ($intercom instanceof CmsInterface) {
                if (!$intercom->ping()) {
                    throw new DeviceException($intercom, 'Устройство не доступно');
                }

                $intercom->setCmsModel($entrance['cms']);
                $intercom->clearCms($entrance['cms']);

                $cms_allocation = container(HouseFeature::class)->getCms($entrance['entranceId']);

                foreach ($cms_allocation as $item) {
                    $intercom->setCmsApartmentDeffer(new CmsApartment($item['cms'] + 1, intval($item['dozen']), intval($item['unit']), intval($item['apartment'])));
                }

                $intercom->defferCms();
            }
        } catch (Throwable $throwable) {
            if ($throwable instanceof DeviceException) {
                throw $throwable;
            }

            $this->logger?->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}