<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Key;

use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;

interface KeyHandlerInterface
{
    /**
     * @param array<int, HouseFlat> $flats flat -> HouseFlat
     * @param HouseEntrance $entrance
     * @return void
     */
    public function handleKey(array $flats, HouseEntrance $entrance): void;
}