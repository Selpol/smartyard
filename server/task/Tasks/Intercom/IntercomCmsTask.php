<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Selpol\Task\Task;
use Throwable;

class IntercomCmsTask extends Task
{
    public int $entranceId;

    public function __construct(int $entranceId)
    {
        parent::__construct('Конфигурация CMS (' . $entranceId . ')');

        $this->entranceId = $entranceId;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onTask(): bool
    {
        $entrance = backend('households')->getEntrance($this->entranceId);

        if (!$entrance || $entrance['shared'])
            return false;

        $domophone = backend('households')->getDomophone($entrance['domophoneId']);

        if (!$domophone)
            return false;

        $this->cms($entrance, $domophone);

        return true;
    }

    private function cms(array $entrance, array $domophone): void
    {
        try {
            $device = intercom($domophone['model'], $domophone['url'], $domophone['credentials']);

            if (!$device->ping())
                throw new RuntimeException('Устройство не доступно');

            $cms_allocation = backend('households')->getCms($entrance['entranceId']);

            foreach ($cms_allocation as $item)
                $device->addCmsDefer($item['cms'] + 1, $item['dozen'], $item['unit'], $item['apartment']);

            $device->deffer();
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }
}