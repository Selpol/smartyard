<?php

namespace Selpol\Feature\House;

use Selpol\Feature\Feature;
use Selpol\Feature\House\Internal\InternalHouseFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalHouseFeature::class)]
readonly abstract class HouseFeature extends Feature
{
    abstract function createEntrance(int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool|int;

    abstract function getEntrance(int $entranceId): array|bool;

    abstract function getEntranceWithPrefix(int $entranceId, int $prefix): array|bool;

    abstract function getEntrances(string $by, mixed $query): bool|array;

    abstract function addEntrance(int $houseId, int $entranceId, int $prefix): bool|int;

    abstract function modifyEntrance(int $entranceId, int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool;

    abstract function deleteEntrance(int $entranceId, int $houseId): bool;

    abstract function destroyEntrance(int $entranceId): bool;

    abstract function getFlat(int $flatId, bool $withBlock = false): bool|array;

    abstract function getFlatPlog(int $flatId): ?int;

    abstract function getFlats(string $by, mixed $params, bool $withBlock = false): bool|array;

    abstract function addFlat(int $houseId, int $floor, string $flat, string $code, array $entrances, array|bool|null $apartmentsAndLevels, string|null $openCode, int $plog, int $autoOpen, int $whiteRabbit, int $sipEnabled, ?string $sipPassword, ?string $comment): bool|int|string;

    abstract function modifyFlat(int $flatId, array $params): bool;

    abstract function addEntranceToFlat(int $entranceId, int $flatId, int $apartment): bool;

    abstract function deleteFlat(int $flatId): bool;

    abstract function getSharedEntrances(int|bool $houseId = false): bool|array;

    abstract public function getCms(int $entranceId): bool|array;

    abstract public function setCms(int $entranceId, array $cms): bool;

    abstract public function getDomophoneIdByEntranceCameraId(int $camera_id): ?int;

    abstract public function getIntercomOpenDataByEntranceCameraId(int $camera_id): ?array;

    abstract public function getSubscribers(string $by, mixed $query): bool|array;

    abstract public function addSubscriber(string $mobile, string|null $name = null, string|null $patronymic = null, string|null $audJti = null, int|bool $flatId = false, array|bool $message = false): int|bool;

    abstract public function modifySubscriber(int $subscriberId, array $params = []): bool|int;

    abstract public function getSubscribersInFlat(int $flatId): array;

    abstract public function addSubscriberToFlat(int $flatId, int $subscriberId, int $role): bool;

    abstract public function updateSubscriberRoleInFlat(int $flatId, int $subscriberId, int $role): bool;

    abstract public function removeSubscriberFromFlat(int $flatId, int $subscriberId): bool|int;

    abstract public function getCameras(string $by, int $params): array;
}