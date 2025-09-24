<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Key;

use Selpol\Entity\Model\House\HouseEntrance;

interface KeyHandlerInterface
{
    /**
     * @param HouseEntrance $entrance
     * @return void
     */
    public function handleKey(HouseEntrance $entrance): void;
}