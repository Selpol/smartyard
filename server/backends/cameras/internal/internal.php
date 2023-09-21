<?php

namespace backends\cameras;

class internal extends cameras
{
    /**
     * @inheritDoc
     */
    public function getCameras($by = false, $params = false)
    {
        $q = "select * from cameras order by camera_id";
        $p = false;

        switch ($by) {
            case "id":
                $q = "select * from cameras where camera_id = :camera_id";
                $p = [
                    "camera_id" => $params,
                ];
        }

        return $this->db->get($q, $p, [
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

    /**
     * @inheritDoc
     */
    public function getCamera(int $cameraId)
    {
        $cams = $this->getCameras("id", $cameraId);

        if (count($cams) === 1) {
            return $cams[0];
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function addCamera($enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment)
    {
        if (!$model) {
            return false;
        }

        $configs = backend("configs");
        $models = $configs->getCamerasModels();

        if (!@$models[$model]) {
            return false;
        }

        if (!check_string($url)) {
            return false;
        }

        return $this->db->insert("insert into cameras (enabled, model, url, stream, credentials, name, dvr_stream, timezone, lat, lon, direction, angle, distance, frs, md_left, md_top, md_width, md_height, common, comment) values (:enabled, :model, :url, :stream, :credentials, :name, :dvr_stream, :timezone, :lat, :lon, :direction, :angle, :distance, :frs, :md_left, :md_top, :md_width, :md_height, :common, :comment)", [
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
     * @inheritDoc
     */
    public function modifyCamera(int $cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment)
    {
        if (!$model) {
            last_error("noModel");
            return false;
        }

        $configs = backend("configs");
        $models = $configs->getCamerasModels();

        if (!@$models[$model]) {
            last_error("modelUnknown");
            return false;
        }

        if (!check_string($url)) {
            return false;
        }

        return $this->db->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, name = :name, dvr_stream = :dvr_stream, timezone = :timezone, lat = :lat, lon = :lon, direction = :direction, angle = :angle, distance = :distance, frs = :frs, md_left = :md_left, md_top = :md_top, md_width = :md_width, md_height = :md_height, common = :common, comment = :comment where camera_id = $cameraId", [
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
     * @inheritDoc
     */
    public function deleteCamera(int $cameraId)
    {
        return $this->db->modify("delete from cameras where camera_id = $cameraId");
    }
}
