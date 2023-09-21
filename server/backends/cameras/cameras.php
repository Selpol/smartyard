<?php

namespace backends\cameras;

use backends\backend;

abstract class cameras extends backend
{
    /**
     * @return false|array
     */
    abstract public function getCameras($by = false, $params = false);

    /**
     * @param $cameraId
     * @return false|array
     */
    abstract public function getCamera(int $cameraId);

    /**
     * @param $enabled
     * @param $model
     * @param $url
     * @param $stream
     * @param $credentials
     * @param $name
     * @param $dvrStream
     * @param $timezone
     * @param $lat
     * @param $lon
     * @param $direction
     * @param $angle
     * @param $distance
     * @param $frs
     * @param $mdLeft
     * @param $mdTop
     * @param $mdWidth
     * @param $mdHeight
     * @param $common
     * @param $comment
     * @return false|integer
     */
    abstract public function addCamera($enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment);

    /**
     * @param $cameraId
     * @param $enabled
     * @param $model
     * @param $url
     * @param $stream
     * @param $credentials
     * @param $name
     * @param $dvrStream
     * @param $timezone
     * @param $lat
     * @param $lon
     * @param $direction
     * @param $angle
     * @param $distance
     * @param $frs
     * @param $mdLeft
     * @param $mdTop
     * @param $mdWidth
     * @param $mdHeight
     * @param $common
     * @param $comment
     * @return boolean
     */
    abstract public function modifyCamera(int $cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment);

    /**
     * @param $cameraId
     * @return boolean
     */
    abstract public function deleteCamera(int $cameraId);
}
