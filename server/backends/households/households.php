<?php

namespace backends\households;

use backends\backend;

abstract class households extends backend
{

    /**
     * @param $houseId
     * @param $entranceType
     * @param $entrance
     * @param $lat
     * @param $lon
     * @param $shared
     * @param $plog
     * @param $prefix
     * @param $callerId
     * @param $domophoneId
     * @param $domophoneOutput
     * @param $cms
     * @param $cmsType
     * @param $cameraId
     * @param $locksDisabled
     * @param $cmsLevels
     * @return boolean|integer
     */
    abstract function createEntrance(int $houseId, $entranceType, $entrance, $lat, $lon, $shared, int $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, int $cmsType, $cameraId, $locksDisabled, $cmsLevels);

    abstract function getEntrance(int $entranceId): array|bool;

    /**
     * @param $by
     * @param $query
     * @return false|array
     */
    abstract function getEntrances($by, $query);

    /**
     * @param $houseId
     * @param $entranceId
     * @param $prefix
     * @return boolean
     */
    abstract function addEntrance(int $houseId, int $entranceId, int $prefix);

    /**
     * @param $entranceId
     * @param $houseId
     * @param $entranceType
     * @param $entrance
     * @param $lat
     * @param $lon
     * @param $shared
     * @param $plog
     * @param $prefix
     * @param $callerId
     * @param $domophoneId
     * @param $domophoneOutput
     * @param $cms
     * @param $cmsType
     * @param $cameraId
     * @param $locksDisabled
     * @param $cmsLevels
     * @return boolean
     */
    abstract function modifyEntrance(int $entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, int $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, int $cmsType, $cameraId, $locksDisabled, $cmsLevels);

    /**
     * @param $entranceId
     * @param $houseId
     * @return boolean
     */
    abstract function deleteEntrance(int $entranceId, int $houseId);

    /**
     * @param $entranceId
     * @return boolean
     */
    abstract function destroyEntrance(int $entranceId);

    /**
     * @param $flatId
     * @return boolean|array
     */
    abstract function getFlat(int $flatId);

    /**
     * Получить значение plog у квартиры
     * @param int $flatId идентификатор квартиры
     * @return int|null
     */
    abstract function getFlatPlog(int $flatId): ?int;

    /**
     * @param $by
     * @param $params
     * @return boolean|array
     */
    abstract function getFlats($by, $params);

    /**
     * @param $houseId
     * @param $floor
     * @param $flat
     * @param $code
     * @param $entrances
     * @param $apartmentsAndLevels
     * @param $manualBlock
     * @param $adminBlock
     * @param $openCode
     * @param $plog
     * @param $autoOpen
     * @param $whiteRabbit
     * @param $sipEnabled
     * @param $sipPassword
     * @return boolean|integer
     */
    abstract function addFlat(int $houseId, $floor, $flat, $code, $entrances, $apartmentsAndLevels, int $manualBlock, int $adminBlock, $openCode, int $plog, int $autoOpen, int $whiteRabbit, int $sipEnabled, $sipPassword);

    /**
     * @param $flatId
     * @param $params
     * @return boolean
     */
    abstract function modifyFlat(int $flatId, $params);

    /**
     * @param $flatId
     * @return boolean
     */
    abstract function deleteFlat(int $flatId);

    abstract function doorOpened(int $flatId): bool|int;

    /**
     * @param $houseId
     * @return false|array
     */
    abstract function getSharedEntrances(int|bool $houseId = false);

    /**
     * @param $entranceId
     * @return false|array
     */
    abstract public function getCms(int $entranceId);

    /**
     * @param $entranceId
     * @param $cms
     * @return boolean
     */
    abstract public function setCms(int $entranceId, $cms);

    /**
     * @param $by
     * @param $query
     * @return mixed
     */
    abstract public function getDomophones($by = "all", $query = -1);

    /**
     * @param int $camera_id
     * @return int|null
     */
    abstract public function getDomophoneIdByEntranceCameraId(int $camera_id): ?int;

    /**
     * @param $enabled
     * @param $model
     * @param $server
     * @param $url
     * @param $credentials
     * @param $dtmf
     * @param $nat
     * @param $comment
     * @return false|integer
     */
    abstract public function addDomophone($enabled, $model, $server, $url, $credentials, $dtmf, int $nat, $comment);

    /**
     * @param $domophoneId
     * @param $enabled
     * @param $model
     * @param $server
     * @param $url
     * @param $credentials
     * @param $dtmf
     * @param $firstTime
     * @param $nat
     * @param $locksAreOpen
     * @param $comment
     * @return boolean
     */
    abstract public function modifyDomophone(int $domophoneId, $enabled, $model, $server, $url, $credentials, $dtmf, int $firstTime, int $nat, int $locksAreOpen, $comment);

    /**
     * @param $domophoneId
     * @return boolean
     */
    abstract public function deleteDomophone(int $domophoneId);

    /**
     * @param $domophoneId
     * @return false|array
     */
    abstract public function getDomophone(int $domophoneId);

    /**
     * @param $by - "id", "mobile", "aud_jti", "flat", "...?"
     * @param $query
     * @return false|array
     */
    abstract public function getSubscribers($by, $query);

    abstract public function addSubscriber(string|int $mobile, string|null $name = null, string|null $patronymic = null, string|null $audJti = null, int|bool $flatId = false, array|bool $message = false): int|bool;

    /**
     * @param $subscriberId
     * @param $params
     * @return boolean
     */
    abstract public function modifySubscriber(int $subscriberId, $params = []);

    abstract public function deleteSubscriber(int $subscriberId);

    abstract public function addSubscriberToFlat(int $flatId, int $subscriberId): bool;

    abstract public function removeSubscriberFromFlat(int $flatId, int $subscriberId): bool|int;

    abstract public function setSubscriberFlats(int $subscriberId, $flats): bool;

    /**
     * @param $by
     * @param $query
     * @return mixed
     */
    abstract public function getKeys($by, $query);

    abstract public function getKey(int $keyId): array|false;

    abstract public function addKey(string $rfId, int $accessType, $accessTo, string $comments): bool|int|string;

    abstract public function modifyKey(int $keyId, string $comments): bool|int;

    abstract public function deleteKey(int $keyId): bool|int;

    /**
     * @param $token
     * @return boolean
     */
    abstract public function dismissToken($token);

    /**
     * @param $by
     * @param $params
     * @return array|false
     */
    abstract public function getCameras($by, $params);

    /**
     * @param $to
     * @param $id
     * @param $cameraId
     * @return mixed
     */
    abstract public function addCamera($to, $id, $cameraId);

    /**
     * @param $from
     * @param $id
     * @param $cameraId
     * @return mixed
     */
    abstract public function unlinkCamera($from, $id, $cameraId);
}
