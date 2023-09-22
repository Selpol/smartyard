<?php

namespace Selpol\Feature\Camera;

use Selpol\Feature\Feature;

abstract class CameraFeature extends Feature
{
    abstract public function getCameras(string|bool $by = false, mixed $params = false): array|bool;

    abstract public function getCamera(int $cameraId): array|bool;

    abstract public function addCamera($enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment): bool|int;

    abstract public function modifyCamera(int $cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, int $mdLeft, int $mdTop, int $mdWidth, int $mdHeight, $common, $comment): bool;

    abstract public function deleteCamera(int $cameraId): bool;
}