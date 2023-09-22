<?php

namespace Selpol\Feature\Camera\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Feature\Camera\CameraFeature;

class InternalCameraFeature extends CameraFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getCameras(string|bool $by = false, $params = false): bool|array
    {
        $q = "select * from cameras order by camera_id";
        $p = false;

        if ($by == "id") {
            $q = "select * from cameras where camera_id = :camera_id";
            $p = ["camera_id" => $params];
        }

        return $this->getDatabase()->get($q, $p, [
            "camera_id" => "cameraId",
            "enabled" => "enabled",
            "model" => "model",
            "url" => "url",
            "stream" => "stream",
            "credentials" => "credentials",
            "name" => "name",
            "dvr_stream" => "dvrStream",
            "timezone" => "timezone",
            "lat" => "lat",
            "lon" => "lon",
            "direction" => "direction",
            "angle" => "angle",
            "distance" => "distance",
            "frs" => "frs",
            "md_left" => "mdLeft",
            "md_top" => "mdTop",
            "md_width" => "mdWidth",
            "md_height" => "mdHeight",
            "common" => "common",
            "comment" => "comment"
        ]);
    }

    public function getCamera(int $cameraId): bool|array
    {
        $cams = $this->getCameras("id", $cameraId);

        if (count($cams) === 1)
            return $cams[0];
        else return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addCamera($enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment): bool|int
    {
        if (!$model)
            return false;

        $models = CameraModel::modelsToArray();

        if (!@$models[$model])
            return false;

        if (!check_string($url))
            return false;

        return $this->getDatabase()->insert("insert into cameras (enabled, model, url, stream, credentials, name, dvr_stream, timezone, lat, lon, direction, angle, distance, frs, md_left, md_top, md_width, md_height, common, comment) values (:enabled, :model, :url, :stream, :credentials, :name, :dvr_stream, :timezone, :lat, :lon, :direction, :angle, :distance, :frs, :md_left, :md_top, :md_width, :md_height, :common, :comment)", [
            "enabled" => (int)$enabled,
            "model" => $model,
            "url" => $url,
            "stream" => $stream,
            "credentials" => $credentials,
            "name" => $name,
            "dvr_stream" => $dvrStream,
            "timezone" => $timezone,
            "lat" => $lat,
            "lon" => $lon,
            "direction" => $direction,
            "angle" => $angle,
            "distance" => $distance,
            "frs" => $frs,
            "md_left" => $mdLeft,
            "md_top" => $mdTop,
            "md_width" => $mdWidth,
            "md_height" => $mdHeight,
            "common" => $common,
            "comment" => $comment,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function modifyCamera(int $cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment): bool
    {
        if (!$model) {
            last_error("noModel");
            return false;
        }

        $models = CameraModel::modelsToArray();

        if (!@$models[$model]) {
            last_error("modelUnknown");
            return false;
        }

        if (!check_string($url)) {
            return false;
        }

        return $this->getDatabase()->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, name = :name, dvr_stream = :dvr_stream, timezone = :timezone, lat = :lat, lon = :lon, direction = :direction, angle = :angle, distance = :distance, frs = :frs, md_left = :md_left, md_top = :md_top, md_width = :md_width, md_height = :md_height, common = :common, comment = :comment where camera_id = $cameraId", [
            "enabled" => (int)$enabled,
            "model" => $model,
            "url" => $url,
            "stream" => $stream,
            "credentials" => $credentials,
            "name" => $name,
            "dvr_stream" => $dvrStream,
            "timezone" => $timezone,
            "lat" => $lat,
            "lon" => $lon,
            "direction" => $direction,
            "angle" => $angle,
            "distance" => $distance,
            "frs" => $frs,
            "md_left" => $mdLeft,
            "md_top" => $mdTop,
            "md_width" => $mdWidth,
            "md_height" => $mdHeight,
            "common" => $common,
            "comment" => $comment,
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteCamera(int $cameraId): bool
    {
        return $this->getDatabase()->modify("delete from cameras where camera_id = $cameraId");
    }
}