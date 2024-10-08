<?php

namespace Selpol\Task\Tasks\Plog;

use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Task;

abstract class PlogTask extends Task
{
    protected function __construct(/** @var int Идентификатор устройства */ public int $id,
                                                                            string     $title)
    {
        parent::__construct($title);
    }

    protected function getDomophoneDescription($domophone_output)
    {
        $households = container(HouseFeature::class);

        $result = $households->getEntrances('domophoneId', ['domophoneId' => $this->id, 'output' => $domophone_output]);

        if ($result && $result[0]) {
            return $result[0]['entrance'];
        }

        return false;
    }

    protected function getFlatIdByRfid($rfid): array
    {
        $households = container(HouseFeature::class);

        $flats1 = array_map(self::getFlatId(...), $households->getFlats('rfId', ['rfId' => $rfid]));
        $flats2 = array_map(self::getFlatId(...), $households->getFlats('domophoneId', $this->id));

        return array_intersect($flats1, $flats2);
    }

    protected function getFlatIdByCode($code): array
    {
        $households = container(HouseFeature::class);

        $flats1 = array_map(self::getFlatId(...), $households->getFlats('openCode', ['openCode' => $code]));
        $flats2 = array_map(self::getFlatId(...), $households->getFlats('domophoneId', $this->id));

        return array_intersect($flats1, $flats2);
    }

    protected function getFlatIdByUserPhone($user_phone): bool|array
    {
        $households = container(HouseFeature::class);

        $result = $households->getSubscribers('mobile', $user_phone);

        if ($result && $result[0]) {
            $flats1 = array_map(self::getFlatId(...), $households->getFlats('subscriberId', ['id' => $user_phone]));
            $flats2 = array_map(self::getFlatId(...), $households->getFlats('domophoneId', $this->id));

            return array_intersect($flats1, $flats2);
        }

        return false;
    }

    protected function getFlatIdByPrefixAndNumber(int|string $prefix, int|string $flat_number): ?int
    {
        $households = container(HouseFeature::class);
        $result = $households->getFlats('flatIdByPrefix', ['prefix' => $prefix, 'flatNumber' => $flat_number, 'domophoneId' => $this->id]);

        if ($result && $result[0]) {
            return intval($result[0]['flatId']);
        }

        return false;
    }

    protected function getFlatIdByNumber(int|string $flat_number): ?int
    {
        $households = container(HouseFeature::class);
        $result = $households->getFlats('apartment', ['domophoneId' => $this->id, 'flatNumber' => $flat_number]);

        if ($result && $result[0]) {
            return intval($result[0]['flatId']);
        }

        return false;
    }

    protected function getFlatIdByDomophoneId(): ?int
    {
        $households = container(HouseFeature::class);
        $result = $households->getFlats('domophoneId', $this->id);

        // Only if one apartment is linked
        if ($result && count($result) === 1 && $result[0]) {
            return intval($result[0]['flatId']);
        }

        return false;
    }

    protected function getEntranceCount($flat_id): int
    {
        $households = container(HouseFeature::class);
        $result = $households->getEntrances('flatId', $flat_id);

        if ($result) {
            return count($result);
        }

        return 0;
    }

    protected function getFlatId(array $item)
    {
        return $item['flatId'];
    }
}