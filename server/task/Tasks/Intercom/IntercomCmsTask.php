<?php

namespace Selpol\Task\Tasks\Intercom;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DomophoneService;
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

        if (!$entrance)
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
            $cmses = backend('configs')->getCMSes();

            $panel = container(DomophoneService::class)->get($domophone['model'], $domophone['url'], $domophone['credentials']);

            $cms_model = (string)@$cmses[$entrance['cms']]['model'];

            $cms_allocation = backend('households')->getCms($entrance['entranceId']);

            foreach ($cms_allocation as $item)
                $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
        } catch (Throwable $throwable) {
            logger('intercom')->error($throwable);
        }
    }
}