<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Camera;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Camera\CameraIndexRequest;
use Selpol\Controller\Request\Admin\Camera\CameraStoreRequest;
use Selpol\Controller\Request\Admin\Camera\CameraUpdateRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Selpol\Service\DeviceService;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;
use Throwable;

/**
 * Камера
 */
#[Controller('/admin/camera')]
readonly class CameraController extends AdminRbtController
{
    /**
     * Получить список камер
     */
    #[Get]
    public function index(CameraIndexRequest $request, AuthService $service): ResponseInterface
    {
        $criteria = criteria()
            ->like('comment', $request->comment)
            ->equal('model', $request->model)
            ->like('ip', $request->ip)
            ->like('device_id', $request->device_id)
            ->like('device_model', $request->device_model)
            ->like('device_software_version', $request->device_software_version)
            ->like('device_hardware_version', $request->device_hardware_version)
            ->asc('camera_id');

        if (!$service->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        return self::success(DeviceCamera::fetchPage($request->page, $request->size, $criteria));
    }

    /**
     * Получить камеру
     */
    #[Get('/{id}')]
    public function show(int $id, AuthService $service): ResponseInterface
    {
        $criteria = criteria();

        if (!$service->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        $camera = DeviceCamera::findById($id, $criteria);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        return self::success($camera);
    }

    /**
     * Получить скриншот с камеры
     */
    #[Get('/screenshot/{id}')]
    public function screenshot(int $id, AuthService $service, DeviceService $deviceService): ResponseInterface
    {
        $criteria = criteria();

        if (!$service->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        $camera = DeviceCamera::findById($id, $criteria);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        $device = $deviceService->cameraByEntity($camera);

        return response(headers: ['Content-Type' => ['image/jpeg']])->withBody($device->getScreenshot());
    }

    /**
     * Создать новую камеру
     */
    #[Post()]
    public function store(CameraStoreRequest $request, DeviceService $service): ResponseInterface
    {
        $camera = new DeviceCamera();

        $camera->dvr_server_id = $request->dvr_server_id;
        $camera->frs_server_id = $request->frs_server_id;

        $camera->enabled = $request->enabled;

        $camera->model = $request->model;
        $camera->url = $request->url;
        $camera->stream = $request->stream;
        $camera->credentials = $request->credentials;
        $camera->name = $request->name;
        $camera->dvr_stream = $request->dvr_stream;
        $camera->timezone = $request->timezone;

        $camera->lat = $request->lat;
        $camera->lon = $request->lon;

        $camera->common = $request->common;

        $camera->ip = $request->ip;

        $camera->comment = $request->comment;

        $camera->config = $request->config;

        $camera->hidden = $request->hidden;

        if (!$camera->ip) {
            $ip = gethostbyname(parse_url($camera->url, PHP_URL_HOST));

            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                $camera->ip = $ip;
            }
        }

        $camera->insert();

        try {
            $camera->device_id = null;
            $camera->device_model = null;
            $camera->device_software_version = null;
            $camera->device_hardware_version = null;

            $device = $service->cameraByEntity($camera);

            if ($device) {
                $info = $device->getSysInfo();

                $camera->device_id = $info->deviceId;
                $camera->device_model = $info->deviceModel;
                $camera->device_software_version = $info->softwareVersion;
                $camera->device_hardware_version = $info->hardwareVersion;

                $camera->update();
            }
        } catch (Throwable) {

        }

        if ($camera->frs_server_id) {
            task(new FrsAddStreamTask($camera->frs_server_id, (int) $camera->camera_id))->high()->async();
        }

        return self::success($camera->camera_id);
    }

    /**
     * Обновить камеру
     */
    #[Put('/{id}')]
    public function update(CameraUpdateRequest $request, DeviceService $deviceService, AuthService $authService): ResponseInterface
    {
        $criteria = criteria();

        if (!$authService->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        $camera = DeviceCamera::findById($request->id, $criteria);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        $credentialsUpdate = $camera->credentials != $request->credentials;
        $frsUpdate = $camera->frs_server_id != $request->frs_server_id ? $camera->frs_server_id : null;

        $camera->dvr_server_id = $request->dvr_server_id;
        $camera->frs_server_id = $request->frs_server_id;

        $camera->enabled = $request->enabled;

        $camera->model = $request->model;
        $camera->url = $request->url;
        $camera->stream = $request->stream;
        $camera->credentials = $request->credentials;
        $camera->name = $request->name;
        $camera->dvr_stream = $request->dvr_stream;
        $camera->timezone = $request->timezone;

        $camera->lat = $request->lat;
        $camera->lon = $request->lon;

        $camera->common = $request->common;

        $camera->ip = $request->ip;

        $camera->comment = $request->comment;

        $camera->config = $request->config;

        $camera->hidden = $request->hidden;

        try {
            $device = $deviceService->cameraByEntity($camera);

            if ($device) {
                $info = $device->getSysInfo();

                $camera->device_id = $info->deviceId;
                $camera->device_model = $info->deviceModel;
                $camera->device_software_version = $info->softwareVersion;
                $camera->device_hardware_version = $info->hardwareVersion;
            }
        } catch (Throwable) {

        }

        if (!$camera->ip) {
            $ip = gethostbyname(parse_url($camera->url, PHP_URL_HOST));

            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                $camera->ip = $ip;
            }
        }

        $camera->update();

        if ($credentialsUpdate && $camera->dvr_server_id) {
            dvr($camera->dvr_server_id)->updateCamera($camera);
        }

        if ($frsUpdate) {
            if ($frsUpdate) {
                task(new FrsRemoveStreamTask($frsUpdate, (int) $camera->camera_id))->high()->async();
            }

            if ($camera->frs_server_id) {
                task(new FrsAddStreamTask($camera->frs_server_id, (int) $camera->camera_id))->high()->async();
            }
        }

        return self::success($camera);
    }

    /**
     * Удалить камеру
     */
    #[Delete('/{id}')]
    public function delete(int $id, AuthService $service): ResponseInterface
    {
        $criteria = criteria();

        if (!$service->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        $camera = DeviceCamera::findById($id, $criteria);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        $camera->delete();

        if ($camera->frs_server_id) {
            task(new FrsRemoveStreamTask($camera->frs_server_id, (int) $camera->camera_id))->high()->async();
        }

        return self::success();
    }
}
