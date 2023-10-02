<?php

namespace Selpol\Feature\House\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Feature\Oauth\OauthFeature;
use Throwable;

class InternalHouseFeature extends HouseFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getFlatPlog(int $flatId): ?int
    {
        $result = $this->getDatabase()->get("select plog from houses_flats where house_flat_id = $flatId");

        if ($result && count($result) > 0)
            return $result[0]['plog'];

        return null;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function getFlats(string $by, mixed $params): bool|array
    {
        $q = "";
        $p = [];

        switch ($by) {
            case "flatIdByPrefix":
                // houses_entrances_flats
                $q = "select house_flat_id from houses_entrances_flats
                     where house_flat_id in (
                        select house_flat_id from houses_flats
                        where address_house_id in (
                                select address_house_id from houses_houses_entrances
                                where house_entrance_id in (
                                        select house_entrance_id
                                        from houses_entrances
                                        where house_domophone_id = :house_domophone_id
                                    ) and prefix = :prefix
                            )
                     ) and apartment = :apartment group by house_flat_id";
                $p = ["house_domophone_id" => $params["domophoneId"], "prefix" => $params["prefix"], "apartment" => $params["flatNumber"]];
                break;

            case "apartment":
                // houses_entrances_flats
                $q = "select house_flat_id from houses_entrances_flats
                        where house_flat_id in (
                            select house_flat_id from houses_flats
                            where address_house_id in (
                                    select address_house_id from houses_houses_entrances
                                    where house_entrance_id in (
                                        select house_entrance_id from houses_entrances 
                                        where house_domophone_id = :house_domophone_id
                                    )
                                )
                        ) and apartment = :apartment group by house_flat_id";
                $p = ["house_domophone_id" => $params["domophoneId"], "apartment" => $params["flatNumber"]];
                break;

            case "code":
                $q = "select house_flat_id from houses_flats where code = :code";
                $p = ["code" => $params["code"]];
                break;

            case "openCode":
                $q = "select house_flat_id from houses_flats where open_code = :code";
                $p = ["code" => $params["openCode"]];
                break;

            case "rfId":
                $q = "select house_flat_id from houses_flats where house_flat_id in (select access_to from houses_rfids where access_type = 2 and rfid = :code)";
                $p = ["code" => $params["rfId"]];
                break;

            case "subscriberId":
                $q = "select house_flat_id from houses_flats where house_flat_id in (select house_flat_id from houses_flats_subscribers where house_subscriber_id in (select house_subscriber_id from houses_subscribers_mobile where id = :id))";
                $p = ["id" => $params["id"]];
                break;

            case "houseId":
                $q = "select house_flat_id from houses_flats where address_house_id = :address_house_id order by flat";
                $p = ["address_house_id" => $params];
                break;

            case "domophoneId":
                $q = "select house_flat_id from houses_flats left join houses_entrances_flats using (house_flat_id) left join houses_entrances using (house_entrance_id) where house_domophone_id = :house_domophone_id group by house_flat_id order by house_flat_id";
                $p = ["house_domophone_id" => $params];
                break;
        }

        $flats = $this->getDatabase()->get($q, $p);

        if ($flats) {
            $_flats = [];

            foreach ($flats as $flat)
                $_flats[] = $this->getFlat($flat["house_flat_id"]);

            return $_flats;
        } else {
            return [];
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function getFlat(int $flatId): bool|array
    {
        $flat = $this->getDatabase()->get(
            "select
                        house_flat_id,
                        floor, 
                        flat,
                        code,
                        plog,
                        coalesce(manual_block, 0) manual_block, 
                        coalesce(admin_block, 0) admin_block,
                        coalesce(auto_block, 0) auto_block, 
                        open_code, 
                        auto_open, 
                        white_rabbit, 
                        sip_enabled, 
                        sip_password,
                        last_opened,
                        cms_enabled
                    from houses_flats
                    where house_flat_id = $flatId",
            map: [
                "house_flat_id" => "flatId",
                "floor" => "floor",
                "flat" => "flat",
                "code" => "code",
                "plog" => "plog",
                "manual_block" => "manualBlock",
                "admin_block" => "adminBlock",
                "auto_block" => "autoBlock",
                "open_code" => "openCode",
                "auto_open" => "autoOpen",
                "white_rabbit" => "whiteRabbit",
                "sip_enabled" => "sipEnabled",
                "sip_password" => "sipPassword",
                "last_opened" => "lastOpened",
                "cms_enabled" => "cmsEnabled",
            ],
            options: ["singlify"]);

        if ($flat) {
            $entrances = $this->getDatabase()->get(
                "select
                            house_entrance_id,
                            house_domophone_id, 
                            apartment, 
                            coalesce(houses_entrances_flats.cms_levels, houses_entrances.cms_levels, '') cms_levels,
                            (select count(*) from houses_entrances_cmses where houses_entrances_cmses.house_entrance_id = houses_entrances_flats.house_entrance_id and houses_entrances_cmses.apartment = houses_entrances_flats.apartment) matrix
                        from 
                            houses_entrances_flats
                                left join houses_entrances using (house_entrance_id)
                        where house_flat_id = {$flat["flatId"]}",
                map: [
                    "house_entrance_id" => "entranceId",
                    "apartment" => "apartment",
                    "cms_levels" => "apartmentLevels",
                    "house_domophone_id" => "domophoneId",
                    "matrix" => "matrix"
                ]
            );

            $flat["entrances"] = [];

            foreach ($entrances as $e)
                $flat["entrances"][] = $e;

            return $flat;
        }

        return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function createEntrance(int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool|int
    {
        if (!trim($entranceType) || !trim($entrance)) {
            return false;
        }

        if ($shared && !$prefix) {
            return false;
        }

        if (!$shared) {
            $prefix = 0;
        }

        if (!check_string($callerId)) {
            return false;
        }

        $entranceId = $this->getDatabase()->insert("insert into houses_entrances (entrance_type, entrance, lat, lon, shared, plog, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, locks_disabled, cms_levels) values (:entrance_type, :entrance, :lat, :lon, :shared, :plog, :caller_id, :house_domophone_id, :domophone_output, :cms, :cms_type, :camera_id, :locks_disabled, :cms_levels)", [
            ":entrance_type" => $entranceType,
            ":entrance" => $entrance,
            ":lat" => $lat,
            ":lon" => $lon,
            ":shared" => $shared,
            ":plog" => $plog,
            ":caller_id" => $callerId,
            ":house_domophone_id" => $domophoneId,
            ":domophone_output" => $domophoneOutput,
            ":cms" => $cms,
            ":cms_type" => $cmsType,
            ":camera_id" => $cameraId ?: null,
            ":locks_disabled" => $locksDisabled,
            ":cms_levels" => $cmsLevels,
        ]);

        if (!$entranceId) {
            return false;
        }

        return $this->getDatabase()->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
            ":address_house_id" => $houseId,
            ":house_entrance_id" => $entranceId,
            ":prefix" => $prefix,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function addEntrance(int $houseId, int $entranceId, int $prefix): bool|int
    {
        return $this->getDatabase()->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
            ":address_house_id" => $houseId,
            ":house_entrance_id" => $entranceId,
            ":prefix" => $prefix,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function modifyEntrance(int $entranceId, int $houseId, string $entranceType, string $entrance, float $lat, float $lon, int $shared, int $plog, int $prefix, string $callerId, int $domophoneId, int $domophoneOutput, string $cms, int $cmsType, int $cameraId, int $locksDisabled, string $cmsLevels): bool
    {
        if (!trim($entranceType) || !trim($entrance))
            return false;

        if ($shared && !$prefix)
            return false;

        if (!check_string($callerId))
            return false;

        if (!$shared) {
            if ($this->getDatabase()->modify("delete from houses_houses_entrances where house_entrance_id = $entranceId and address_house_id != $houseId") === false) {
                return false;
            }
            $prefix = 0;
        }

        $r1 = $this->getDatabase()->modify("update houses_houses_entrances set prefix = :prefix where house_entrance_id = $entranceId and address_house_id = $houseId", [
                ":prefix" => $prefix,
            ]) !== false;

        return
            $r1
            and
            $this->getDatabase()->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, lat = :lat, lon = :lon, shared = :shared, plog = :plog, caller_id = :caller_id, house_domophone_id = :house_domophone_id, domophone_output = :domophone_output, cms = :cms, cms_type = :cms_type, camera_id = :camera_id, locks_disabled = :locks_disabled, cms_levels = :cms_levels where house_entrance_id = $entranceId", [
                ":entrance_type" => $entranceType,
                ":entrance" => $entrance,
                ":lat" => $lat,
                ":lon" => $lon,
                ":shared" => $shared,
                ":plog" => $plog,
                ":caller_id" => $callerId,
                ":house_domophone_id" => $domophoneId,
                ":domophone_output" => $domophoneOutput,
                ":cms" => $cms,
                ":cms_type" => $cmsType,
                ":camera_id" => $cameraId ?: null,
                ":locks_disabled" => $locksDisabled,
                ":cms_levels" => $cmsLevels,
            ]) !== false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function deleteEntrance(int $entranceId, int $houseId): bool
    {
        return
            $this->getDatabase()->modify("delete from houses_houses_entrances where address_house_id = $houseId and house_entrance_id = $entranceId") !== false
            and
            $this->getDatabase()->modify("delete from houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances)") !== false
            and
            $this->getDatabase()->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function addFlat(int $houseId, int $floor, string $flat, string $code, array $entrances, array|bool|null $apartmentsAndLevels, int $manualBlock, int $adminBlock, string $openCode, int $plog, int $autoOpen, int $whiteRabbit, int $sipEnabled, ?string $sipPassword): bool|int|string
    {
        $autoOpen = (int)strtotime($autoOpen);

        if (trim($flat)) {

            if ($openCode == "!") {
                // TODO add unique check !!!
                $openCode = 11000 + rand(0, 88999);
            }

            $flatId = $this->getDatabase()->insert("insert into houses_flats (address_house_id, floor, flat, code, manual_block, admin_block, open_code, plog, auto_open, white_rabbit, sip_enabled, sip_password, cms_enabled) values (:address_house_id, :floor, :flat, :code, :manual_block, :admin_block, :open_code, :plog, :auto_open, :white_rabbit, :sip_enabled, :sip_password, 1)", [
                ":address_house_id" => $houseId,
                ":floor" => $floor,
                ":flat" => $flat,
                ":code" => $code,
                ":plog" => $plog,
                ":manual_block" => $manualBlock,
                ":admin_block" => $adminBlock,
                ":open_code" => $openCode,
                ":auto_open" => $autoOpen,
                ":white_rabbit" => $whiteRabbit,
                ":sip_enabled" => $sipEnabled,
                ":sip_password" => $sipPassword,
            ]);

            if ($flatId) {
                for ($i = 0; $i < count($entrances); $i++) {
                    if (!is_int($entrances[$i])) {
                        return false;
                    } else {
                        $ap = $flat;
                        $lv = "";
                        if ($apartmentsAndLevels && @$apartmentsAndLevels[$entrances[$i]]) {
                            $ap = (int)$apartmentsAndLevels[$entrances[$i]]["apartment"];
                            if (!$ap || $ap <= 0 || $ap > 9999) {
                                $ap = $flat;
                            }
                            $lv = @$apartmentsAndLevels[$entrances[$i]]["apartmentLevels"];
                        }
                        if ($this->getDatabase()->modify("insert into houses_entrances_flats (house_entrance_id, house_flat_id, apartment, cms_levels) values (:house_entrance_id, :house_flat_id, :apartment, :cms_levels)", [
                                ":house_entrance_id" => $entrances[$i],
                                ":house_flat_id" => $flatId,
                                ":apartment" => $ap,
                                ":cms_levels" => $lv,
                            ]) === false) {
                            return false;
                        }
                    }
                }
                return $flatId;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function modifyFlat(int $flatId, array $params): bool
    {
        if (array_key_exists("code", $params) && !check_string($params["code"])) {
            last_error("invalidParams");
            return false;
        }

        if (array_key_exists("autoOpen", $params)) {
            $params["autoOpen"] = (int)strtotime($params["autoOpen"]);
        }

        if (@$params["code"] == "!") {
            $params["code"] = md5(guid_v4());
        }

        if (@$params["openCode"] == "!") {
            // TODO add unique check !!!
            $params["openCode"] = 11000 + rand(0, 88999);
        }

        $mod = $this->getDatabase()->modifyEx("update houses_flats set %s = :%s where house_flat_id = $flatId", [
            "floor" => "floor",
            "flat" => "flat",
            "code" => "code",
            "plog" => "plog",
            "manual_block" => "manualBlock",
            "admin_block" => "adminBlock",
            "auto_block" => "autoBlock",
            "open_code" => "openCode",
            "auto_open" => "autoOpen",
            "white_rabbit" => "whiteRabbit",
            "sip_enabled" => "sipEnabled",
            "sip_password" => "sipPassword",
            "cms_enabled" => "cmsEnabled"
        ], $params);

        if ($mod !== false && array_key_exists("flat", $params) && array_key_exists("entrances", $params) && array_key_exists("apartmentsAndLevels", $params) && is_array($params["entrances"]) && is_array($params["apartmentsAndLevels"])) {
            $entrances = $params["entrances"];
            $apartmentsAndLevels = $params["apartmentsAndLevels"];
            if ($this->getDatabase()->modify("delete from houses_entrances_flats where house_flat_id = $flatId") === false) {
                return false;
            }
            for ($i = 0; $i < count($entrances); $i++) {
                $ap = $params["flat"];
                $lv = "";
                if ($apartmentsAndLevels && @$apartmentsAndLevels[$entrances[$i]]) {
                    $ap = (int)$apartmentsAndLevels[$entrances[$i]]["apartment"];
                    if (!$ap || $ap <= 0 || $ap > 9999) {
                        $ap = $params["flat"];
                    }
                    $lv = @$apartmentsAndLevels[$entrances[$i]]["apartmentLevels"];
                }
                if ($this->getDatabase()->modify("insert into houses_entrances_flats (house_entrance_id, house_flat_id, apartment, cms_levels) values (:house_entrance_id, :house_flat_id, :apartment, :cms_levels)", [
                        ":house_entrance_id" => $entrances[$i],
                        ":house_flat_id" => $flatId,
                        ":apartment" => $ap,
                        ":cms_levels" => $lv,
                    ]) === false) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function deleteFlat(int $flatId): bool
    {
        return
            $this->getDatabase()->modify("delete from houses_flats where house_flat_id = $flatId") !== false
            and
            $this->getDatabase()->modify("delete from houses_entrances_flats where house_flat_id not in (select house_flat_id from houses_flats)") !== false
            and
            $this->getDatabase()->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)") !== false
            and
            $this->getDatabase()->modify("delete from houses_cameras_flats where house_flat_id not in (select house_flat_id from houses_flats)") !== false
            and
            $this->getDatabase()->modify("delete from houses_rfids where access_to not in (select house_flat_id from houses_flats) and access_type = 2") !== false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function getSharedEntrances(int|bool $houseId = false): bool|array
    {
        if ($houseId) {
            return $this->getDatabase()->get(
                "select * from (select house_entrance_id, entrance_type, entrance, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id and address_house_id <> $houseId limit 1) address_house_id from houses_entrances where shared = 1 and house_entrance_id in (select house_entrance_id from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances where address_house_id = $houseId))) as t1 where address_house_id is not null",
                map: ["house_entrance_id" => "entranceId", "entrance_type" => "entranceType", "entrance" => "entrance", "address_house_id" => "houseId"]
            );
        } else {
            return $this->getDatabase()->get(
                "select * from (select house_entrance_id, entrance_type, entrance, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id limit 1) address_house_id from houses_entrances where shared = 1) as t1 where address_house_id is not null",
                map: ["house_entrance_id" => "entranceId", "entrance_type" => "entranceType", "entrance" => "entrance", "address_house_id" => "houseId"]
            );
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function destroyEntrance(int $entranceId): bool
    {
        return
            $this->getDatabase()->modify("delete from houses_entrances where house_entrance_id = $entranceId") !== false
            and
            $this->getDatabase()->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
            and
            $this->getDatabase()->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getCms(int $entranceId): bool|array
    {
        return $this->getDatabase()->get("select * from houses_entrances_cmses where house_entrance_id = $entranceId",
            map: [
                "cms" => "cms",
                "dozen" => "dozen",
                "unit" => "unit",
                "apartment" => "apartment",
            ]
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function setCms(int $entranceId, array $cms): bool
    {
        $result = $this->getDatabase()->modify("delete from houses_entrances_cmses where house_entrance_id = $entranceId") !== false;

        foreach ($cms as $e) {
            $result = $result && $this->getDatabase()->modify("insert into houses_entrances_cmses (house_entrance_id, cms, dozen, unit, apartment) values (:house_entrance_id, :cms, :dozen, :unit, :apartment)", [
                    "house_entrance_id" => $entranceId,
                    "cms" => $e["cms"],
                    "dozen" => $e["dozen"],
                    "unit" => $e["unit"],
                    "apartment" => $e["apartment"],
                ]);
        }

        return $result;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getDomophones(string $by = "all", string|int $query = -1): bool|array
    {
        $q = "select * from houses_domophones order by house_domophone_id";
        $r = [
            "house_domophone_id" => "domophoneId",
            "enabled" => "enabled",
            "model" => "model",
            "server" => "server",
            "url" => "url",
            "credentials" => "credentials",
            "dtmf" => "dtmf",
            "first_time" => "firstTime",
            "nat" => "nat",
            "locks_are_open" => "locksAreOpen",
            "comment" => "comment",
            "ip" => "ip",
        ];

        switch ($by) {
            case "house":
                $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_houses_entrances where address_house_id = $query
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
                break;

            case "entrance":
                $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id = $query group by house_domophone_id
                              ) order by house_domophone_id";
                break;

            case "flat":
                $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_entrances_flats where house_flat_id = $query
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
                break;

            case "ip":
                $q = "select * from houses_domophones where ip = '" . long2ip(ip2long($query)) . "'";
                break;

            case "subscriber":
                $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_entrances_flats where house_flat_id in (
                                    select house_flat_id from houses_flats_subscribers where house_subscriber_id = $query
                                  )
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
                break;
        }

        return $this->getDatabase()->get($q, map: $r);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getDomophoneIdByEntranceCameraId(int $camera_id): ?int
    {
        $entrance = $this->getDatabase()->get("select house_domophone_id from houses_entrances where camera_id = $camera_id limit 1");

        if ($entrance && count($entrance) > 0)
            return $entrance[0]['house_domophone_id'];

        return null;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addDomophone(int $enabled, string $model, string $server, string $url, string $credentials, string $dtmf, int $nat, string $comment): bool|int|string
    {
        if (IntercomModel::model($model) === null) {
            last_error("modelUnknown");
            return false;
        }

        if (!trim($server)) {
            last_error("noServer");
            return false;
        }

        if (!trim($url)) {
            return false;
        }

        if (in_array(trim($dtmf), ["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"]) === false) {
            last_error("dtmf");
            return false;
        }

        return $this->getDatabase()->insert("insert into houses_domophones (enabled, model, server, url, credentials, dtmf, nat, comment) values (:enabled, :model, :server, :url, :credentials, :dtmf, :nat, :comment)", [
            "enabled" => $enabled,
            "model" => $model,
            "server" => $server,
            "url" => $url,
            "credentials" => $credentials,
            "dtmf" => $dtmf,
            "nat" => $nat,
            "comment" => $comment,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function modifyDomophone(int $domophoneId, int $enabled, string $model, string $server, string $url, string $credentials, string $dtmf, int $firstTime, int $nat, int $locksAreOpen, string $comment): bool|int
    {
        if (!trim($server)) {
            last_error("noServer");
            return false;
        }

        $models = IntercomModel::modelsToArray();

        if (!@$models[$model]) {
            last_error("modelUnknown");
            return false;
        }

        if (!trim($url)) {
            last_error("noUrl");
            return false;
        }

        if (in_array(trim($dtmf), ["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"]) === false) {
            last_error("dtmf");
            return false;
        }

        return $this->getDatabase()->modify("update houses_domophones set enabled = :enabled, model = :model, server = :server, url = :url, credentials = :credentials, dtmf = :dtmf, first_time = :first_time, nat = :nat, locks_are_open = :locks_are_open, comment = :comment where house_domophone_id = $domophoneId", [
            "enabled" => $enabled,
            "model" => $model,
            "server" => $server,
            "url" => $url,
            "credentials" => $credentials,
            "dtmf" => $dtmf,
            "first_time" => $firstTime,
            "nat" => $nat,
            "locks_are_open" => $locksAreOpen,
            "comment" => $comment,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteDomophone(int $domophoneId): bool
    {
        return
            $this->getDatabase()->modify("delete from houses_domophones where house_domophone_id = $domophoneId") !== false
            &&
            $this->getDatabase()->modify("delete from houses_entrances where house_domophone_id not in (select house_domophone_id from houses_domophones)") !== false
            &&
            $this->getDatabase()->modify("delete from houses_entrances_cmses where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
            &&
            $this->getDatabase()->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
            &&
            $this->getDatabase()->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getDomophone(int $domophoneId): bool|array
    {
        $domophone = $this->getDatabase()->get("select * from houses_domophones where house_domophone_id = $domophoneId",
            map: [
                "house_domophone_id" => "domophoneId",
                "enabled" => "enabled",
                "model" => "model",
                "server" => "server",
                "url" => "url",
                "credentials" => "credentials",
                "dtmf" => "dtmf",
                "first_time" => "firstTime",
                "nat" => "nat",
                "locks_are_open" => "locksAreOpen",
                "comment" => "comment",
                "ip" => "ip"
            ],
            options: ["singlify"]
        );

        if ($domophone)
            $domophone['json'] = IntercomModel::models()[$domophone['model']]->toArray();

        return $domophone;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getSubscribers(string $by, mixed $query): bool|array
    {
        $q = "";
        $p = false;

        switch ($by) {
            case "flatId":
                $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id = :house_flat_id)";
                $p = ["house_flat_id" => (int)$query,];
                break;

            case "mobile":
                $q = "select * from houses_subscribers_mobile where id = :id";
                $p = ["id" => $query,];
                break;

            case "id":
                $q = "select * from houses_subscribers_mobile where house_subscriber_id = :house_subscriber_id";
                $p = ["house_subscriber_id" => (int)$query,];
                break;

            case "authToken":
                $q = "select * from houses_subscribers_mobile where auth_token = :auth_token";
                $p = ["auth_token" => $query];
                break;
            case "aud_jti":
                $q = "select * from houses_subscribers_mobile where aud_jti = :aud_jti";
                $p = ["aud_jti" => $query];
                break;
        }

        $subscribers = $this->getDatabase()->get($q, $p, [
            "house_subscriber_id" => "subscriberId",
            "id" => "mobile",
            "aud_jti" => "audJti",
            "auth_token" => "authToken",
            "platform" => "platform",
            "push_token" => "pushToken",
            "push_token_type" => "tokenType",
            "voip_token" => "voipToken",
            "registered" => "registered",
            "last_seen" => "lastSeen",
            "subscriber_name" => "subscriberName",
            "subscriber_patronymic" => "subscriberPatronymic",
            "voip_enabled" => "voipEnabled",
        ]);

        $addresses = container(AddressFeature::class);

        foreach ($subscribers as &$subscriber) {
            $flats = $this->getDatabase()->get("select house_flat_id, role, flat, address_house_id from houses_flats_subscribers left join houses_flats using (house_flat_id) where house_subscriber_id = :house_subscriber_id",
                ["house_subscriber_id" => $subscriber["subscriberId"]],
                ["house_flat_id" => "flatId", "role" => "role", "flat" => "flat", "address_house_id" => "addressHouseId"]
            );

            foreach ($flats as &$flat)
                $flat["house"] = $addresses->getHouse($flat["addressHouseId"]);
            $subscriber["flats"] = $flats;
        }

        return $subscribers;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriber(string $mobile, string|null $name = null, string|null $patronymic = null, string|null $audJti = null, int|bool $flatId = false, array|bool $message = false): int|bool
    {
        if (
            !check_string($mobile, ["minLength" => 11, "maxLength" => 11, "validChars" => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']]) ||
            !check_string($name, ["maxLength" => 32]) ||
            !check_string($patronymic, ["maxLength" => 32])
        ) {
            last_error("invalidParams");
            return false;
        }

        $subscriber = $this->getDatabase()->get(
            "select house_subscriber_id from houses_subscribers_mobile where id = :mobile",
            ['mobile' => $mobile],
            options: ['singlify']
        );

        $subscriberId = $subscriber ? $subscriber['house_subscriber_id'] : null;

        if (!$subscriberId) {
            $subscriberId = $this->getDatabase()->insert("insert into houses_subscribers_mobile (id, aud_jti, subscriber_name, subscriber_patronymic, registered, voip_enabled) values (:mobile, :aud_jti, :subscriber_name, :subscriber_patronymic, :registered, 1)", [
                "mobile" => $mobile,
                "aud_jti" => $audJti,
                "subscriber_name" => $name,
                "subscriber_patronymic" => $patronymic,
                "registered" => time(),
            ]);
        } else if (trim($name) && trim($patronymic)) {
            $this->modifySubscriber($subscriberId, [
                "subscriberName" => $name,
                "subscriberPatronymic" => $patronymic,
            ]);
        }

        if ($subscriberId && $flatId) {
            if ($message)
                container(InboxFeature::class)->sendMessage($subscriberId, $message['title'], $message['msg'], action: "newAddress");

            if (!$this->getDatabase()->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, 1)", [
                "house_subscriber_id" => $subscriberId,
                "house_flat_id" => $flatId,
            ])) {
                return false;
            }
        }

        try {
            $id = container(OauthFeature::class)->register($mobile);

            if ($id)
                $this->modifySubscriber($subscriberId, ['audJti' => $id]);
        } catch (Throwable $throwable) {
            logger('subscriber')->error($throwable);
        }

        return $subscriberId;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function modifySubscriber(int $subscriberId, array $params = []): bool|int
    {
        $db = $this->getDatabase();

        if (@$params["mobile"]) {
            if (!check_string($params["mobile"], ["minLength" => 6, "maxLength" => 32, "validChars" => ['+', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9']])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set id = :id where house_subscriber_id = $subscriberId", ["id" => $params["mobile"]]) === false) {
                return false;
            }
        }

        $audJti = array_key_exists('audJti', $params) ? $params['audJti'] : null;

        if ($audJti === null && array_key_exists('mobile', $params)) {
            try {
                $audJti = container(OauthFeature::class)->register($params['mobile']);
            } catch (Throwable $throwable) {
                logger('subscriber')->error($throwable);
            }
        }

        if ($audJti) {
            if (!check_string($params['audJti'])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set aud_jti = :aud_jti where house_subscriber_id = $subscriberId", ["aud_jti" => $audJti]) === false) {
                return false;
            }
        }

        if (@$params["subscriberName"] || @$params["forceNames"]) {
            if (!check_string($params["subscriberName"], ["maxLength" => 32])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set subscriber_name = :subscriber_name where house_subscriber_id = $subscriberId", ["subscriber_name" => $params["subscriberName"]]) === false) {
                return false;
            }
        }

        if (@$params["subscriberPatronymic"] || @$params["forceNames"]) {
            if (!check_string($params["subscriberPatronymic"], ["maxLength" => 32])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set subscriber_patronymic = :subscriber_patronymic where house_subscriber_id = $subscriberId", ["subscriber_patronymic" => $params["subscriberPatronymic"]]) === false) {
                return false;
            }
        }

        if (@$params["authToken"]) {
            if (!check_string($params["authToken"])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set auth_token = :auth_token where house_subscriber_id = $subscriberId", ["auth_token" => $params["authToken"]]) === false) {
                return false;
            }
        }

        if (array_key_exists("platform", $params)) {
            if ($db->modify("update houses_subscribers_mobile set platform = :platform where house_subscriber_id = $subscriberId", ["platform" => $params["platform"]]) === false) {
                return false;
            }
        }

        if (@$params["pushToken"]) {
            if (!check_string($params["pushToken"])) {
                last_error("invalidParams");
                return false;
            }

            if ($db->modify("update houses_subscribers_mobile set push_token = :push_token where house_subscriber_id = $subscriberId", ["push_token" => $params["pushToken"]]) === false) {
                return false;
            }
        }

        if (array_key_exists("tokenType", $params)) {
            if ($db->modify("update houses_subscribers_mobile set push_token_type = :push_token_type where house_subscriber_id = $subscriberId", ["push_token_type" => $params["tokenType"]]) === false) {
                return false;
            }
        }

        if (@$params["voipToken"]) {
            if ($db->modify("update houses_subscribers_mobile set voip_token = :voip_token where house_subscriber_id = $subscriberId", ["voip_token" => $params["voipToken"]]) === false) {
                return false;
            }
        }

        if (array_key_exists("voipEnabled", $params)) {
            if ($db->modify("update houses_subscribers_mobile set voip_enabled = :voip_enabled where house_subscriber_id = $subscriberId", ["voip_enabled" => $params["voipEnabled"]]) === false) {
                return false;
            }
        }

        if ($db->modify("update houses_subscribers_mobile set last_seen = :last_seen where house_subscriber_id = $subscriberId", ["last_seen" => time()]) === false) {
            return false;
        }

        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteSubscriber(int $subscriberId): bool|int
    {
        $result = $this->getDatabase()->modify("delete from houses_subscribers_mobile where house_subscriber_id = $subscriberId");

        if ($result === false) return false;
        else return $this->getDatabase()->modify("delete from houses_flats_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriberToFlat(int $flatId, int $subscriberId): bool
    {
        return $this->getDatabase()->insert(
            "insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, :role)",
            [
                "house_subscriber_id" => $subscriberId,
                "house_flat_id" => $flatId,
                "role" => 1,
            ]
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function removeSubscriberFromFlat(int $flatId, int $subscriberId): bool|int
    {
        return $this->getDatabase()->modify("delete from houses_flats_subscribers where house_subscriber_id = :house_subscriber_id and house_flat_id = :house_flat_id", [
            "house_flat_id" => $flatId,
            "house_subscriber_id" => $subscriberId,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function setSubscriberFlats(int $subscriberId, array $flats): bool
    {
        if (!$this->getDatabase()->modify("delete from houses_flats_subscribers where house_subscriber_id = $subscriberId")) {
            return false;
        }

        foreach ($flats as $flatId => $owner) {
            if (!$this->getDatabase()->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, :role)", [
                "house_subscriber_id" => $subscriberId,
                "house_flat_id" => $flatId,
                "role" => $owner ? 0 : 1,
            ])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getKeys(string $by, ?int $query): bool|array
    {
        $q = "";
        $p = false;

        if ($by == "flatId") {
            $q = "select * from houses_rfids where access_to = :flat_id and access_type = 2";
            $p = [
                "flat_id" => (int)$query,
            ];
        }

        return $this->getDatabase()->get($q, $p, [
            "house_rfid_id" => "keyId",
            "rfid" => "rfId",
            "access_type" => "accessType",
            "access_to" => "accessTo",
            "last_seen" => "lastSeen",
            "comments" => "comments",
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getKey(int $keyId): array|false
    {
        return $this->getDatabase()->get('select * from houses_rfids where house_rfid_id = :key_id', ['key_id' => $keyId], [
            "house_rfid_id" => "keyId",
            "rfid" => "rfId",
            "access_type" => "accessType",
            "access_to" => "accessTo",
            "last_seen" => "lastSeen",
            "comments" => "comments",
        ], ['singlify']);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addKey(string $rfId, int $accessType, $accessTo, string $comments): bool|int|string
    {
        if (!check_string($rfId, ["minLength" => 6, "maxLength" => 32]) || !check_string($rfId, ["minLength" => 6, "maxLength" => 32]) || !check_string($comments, ["maxLength" => 128])) {
            last_error("invalidParams");
            return false;
        }

        return $this->getDatabase()->insert("insert into houses_rfids (rfid, access_type, access_to, comments) values (:rfid, :access_type, :access_to, :comments)", [
            "rfid" => $rfId,
            "access_type" => $accessType,
            "access_to" => $accessTo,
            "comments" => $comments,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteKey(int $keyId): bool|int
    {
        return $this->getDatabase()->modify("delete from houses_rfids where house_rfid_id = $keyId");
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function modifyKey(int $keyId, string $comments): bool|int
    {
        return $this->getDatabase()->modify("update houses_rfids set comments = :comments where house_rfid_id = $keyId", ["comments" => $comments]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function doorOpened(int $flatId): bool|int
    {
        return $this->getDatabase()->modify("update houses_flats set last_opened = :now where house_flat_id = $flatId", ["now" => time()]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function getEntrance(int $entranceId): array|bool
    {
        return $this->getDatabase()->get("select house_entrance_id, entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled, plog from houses_entrances where house_entrance_id = $entranceId order by entrance_type, entrance",
            map: [
                "house_entrance_id" => "entranceId",
                "entrance_type" => "entranceType",
                "entrance" => "entrance",
                "lat" => "lat",
                "lon" => "lon",
                "shared" => "shared",
                "plog" => "plog",
                "caller_id" => "callerId",
                "house_domophone_id" => "domophoneId",
                "domophone_output" => "domophoneOutput",
                "cms" => "cms",
                "cms_type" => "cmsType",
                "camera_id" => "cameraId",
                "cms_levels" => "cmsLevels",
                "locks_disabled" => "locksDisabled",
            ],
            options: ["singlify"]
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function dismissToken(string $token): bool
    {
        return
            $this->getDatabase()->modify("update houses_subscribers_mobile set push_token = null where push_token = :push_token", ["push_token" => $token])
            or
            $this->getDatabase()->modify("update houses_subscribers_mobile set voip_token = null where voip_token = :voip_token", ["voip_token" => $token]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    function getEntrances(string $by, mixed $query): bool|array
    {
        $where = '';

        $p = [];
        $q = '';

        switch ($by) {
            case "domophoneId":
                $where = "house_domophone_id = :house_domophone_id and domophone_output = :domophone_output";
                $p = [
                    "house_domophone_id" => $query["domophoneId"],
                    "domophone_output" => $query["output"],
                ];
                break;

            case "houseId":
                $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, prefix, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_houses_entrances left join houses_entrances using (house_entrance_id) where address_house_id = $query order by entrance_type, entrance";
                break;

            case "flatId":
                $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, prefix, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_houses_entrances left join houses_entrances using (house_entrance_id) where house_entrance_id in (select house_entrance_id from houses_entrances_flats where house_flat_id = $query) order by entrance_type, entrance";
                break;
        }

        if (!$q) {
            $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_entrances left join houses_houses_entrances using (house_entrance_id) where $where order by entrance_type, entrance";
        }

        return $this->getDatabase()->get($q,
            $p,
            [
                "address_house_id" => "houseId",
                "house_entrance_id" => "entranceId",
                "entrance_type" => "entranceType",
                "entrance" => "entrance",
                "lat" => "lat",
                "lon" => "lon",
                "shared" => "shared",
                "plog" => "plog",
                "prefix" => "prefix",
                "caller_id" => "callerId",
                "house_domophone_id" => "domophoneId",
                "domophone_output" => "domophoneOutput",
                "cms" => "cms",
                "cms_type" => "cmsType",
                "camera_id" => "cameraId",
                "cms_levels" => "cmsLevels",
                "locks_disabled" => "locksDisabled",
            ]
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addCamera(string $to, int $id, int $cameraId): bool|int|string
    {
        return match ($to) {
            "house" => $this->getDatabase()->insert("insert into houses_cameras_houses (camera_id, address_house_id) values ($cameraId, $id)"),
            "flat" => $this->getDatabase()->insert("insert into houses_cameras_flats (camera_id, house_flat_id) values ($cameraId, $id)"),
            "subscriber" => $this->getDatabase()->insert("insert into houses_cameras_subscribers (camera_id, house_subscriber_id) values ($cameraId, $id)"),
            default => false,
        };
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function unlinkCamera(string $from, int $id, int $cameraId): bool|int
    {
        return match ($from) {
            "house" => $this->getDatabase()->modify("delete from houses_cameras_houses where camera_id = $cameraId and address_house_id = $id"),
            "flat" => $this->getDatabase()->modify("delete from houses_cameras_flats where camera_id = $cameraId and house_flat_id = $id"),
            "subscriber" => $this->getDatabase()->modify("delete from houses_cameras_subscribers where camera_id = $cameraId and house_subscriber_id = $id"),
            default => false,
        };
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getCameras(string $by, int $params): array
    {
        $query = match ($by) {
            'id' => "select camera_id from cameras where camera_id = $params",
            'houseId' => "select camera_id from houses_cameras_houses where address_house_id = $params",
            'flatId' => "select camera_id from houses_cameras_flats where house_flat_id = $params",
            "subscriberId" => "select camera_id from houses_cameras_subscribers where house_subscriber_id = $params",
            default => null
        };

        if ($query) {
            $list = [];

            $ids = $this->getDatabase()->get($query, map: ["camera_id" => "cameraId"]);

            foreach ($ids as $id) {
                $cam = container(CameraFeature::class)->getCamera($id["cameraId"]);

                $list[] = $cam;
            }

            return $list;
        } else return [];
    }
}