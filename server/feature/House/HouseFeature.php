<?php

namespace Selpol\Feature\House;

use Selpol\Feature\Feature;

abstract class HouseFeature extends Feature
{
    abstract function createEntrance(int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool|int;

    abstract function getEntrance(int $entranceId): array|bool;

    abstract function getEntrances(string $by, mixed $query): bool|array;

    abstract function addEntrance(int $houseId, int $entranceId, int $prefix): bool|int;

    abstract function modifyEntrance(int $entranceId, int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool;

    abstract function deleteEntrance(int $entranceId, int $houseId): bool;

    abstract function destroyEntrance(int $entranceId): bool;

    abstract function getFlat(int $flatId): bool|array;

    abstract function getFlatPlog(int $flatId): ?int;

    abstract function getFlats(string $by, mixed $params): bool|array;

    abstract function addFlat(int $houseId, int $floor, string $flat, string $code, array $entrances, array|bool|null $apartmentsAndLevels, int $manualBlock, int $adminBlock, string $openCode, int $plog, int $autoOpen, int $whiteRabbit, int $sipEnabled, ?string $sipPassword): bool|int|string;

    abstract function modifyFlat(int $flatId, array $params): bool;

    abstract function deleteFlat(int $flatId): bool;

    abstract function doorOpened(int $flatId): bool|int;

    abstract function getSharedEntrances(int|bool $houseId = false): bool|array;

    abstract public function getCms(int $entranceId): bool|array;

    abstract public function setCms(int $entranceId, array $cms): bool;

    abstract public function getDomophones(string $by = "all", string|int $query = -1): bool|array;

    abstract public function getDomophoneIdByEntranceCameraId(int $camera_id): ?int;

    abstract public function addDomophone(int $enabled, string $model, string $server, string $url, string $credentials, string $dtmf, int $nat, string $comment): bool|int|string;

    abstract public function modifyDomophone(int $domophoneId, int $enabled, string $model, string $server, string $url, string $credentials, string $dtmf, int $firstTime, int $nat, int $locksAreOpen, string $comment): bool|int;

    abstract public function deleteDomophone(int $domophoneId): bool;

    abstract public function getDomophone(int $domophoneId): bool|array;

    abstract public function getSubscribers(string $by, mixed $query): bool|array;

    abstract public function addSubscriber(string $mobile, string|null $name = null, string|null $patronymic = null, string|null $audJti = null, int|bool $flatId = false, array|bool $message = false): int|bool;

    abstract public function modifySubscriber(int $subscriberId, array $params = []): bool|int;

    abstract public function deleteSubscriber(int $subscriberId): bool|int;

    abstract public function addSubscriberToFlat(int $flatId, int $subscriberId): bool;

    abstract public function removeSubscriberFromFlat(int $flatId, int $subscriberId): bool|int;

    abstract public function setSubscriberFlats(int $subscriberId, array $flats): bool;

    abstract public function getKeys(string $by, ?int $query): bool|array;

    abstract public function getKey(int $keyId): array|false;

    abstract public function addKey(string $rfId, int $accessType, $accessTo, string $comments): bool|int|string;

    abstract public function modifyKey(int $keyId, string $comments): bool|int;

    abstract public function deleteKey(int $keyId): bool|int;

    abstract public function dismissToken(string $token): bool;

    abstract public function getCameras(string $by, int $params): array;

    abstract public function addCamera(string $to, int $id, int $cameraId): bool|int|string;

    abstract public function unlinkCamera(string $from, int $id, int $cameraId): bool|int;
}