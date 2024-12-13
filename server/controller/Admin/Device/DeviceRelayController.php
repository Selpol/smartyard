<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Device;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Device\DeviceRelayIndexRequest;
use Selpol\Controller\Request\Admin\Device\DeviceRelayStoreRequest;
use Selpol\Controller\Request\Admin\Device\DeviceRelayUpdateRequest;
use Selpol\Entity\Model\Device\DeviceRelay;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Устройство-реле
 */
#[Controller('/admin/device/relay')]
readonly class DeviceRelayController extends AdminRbtController
{
    /**
     * Получить список устройств реле
     */
    #[Get]
    public function index(DeviceRelayIndexRequest $request): ResponseInterface
    {
        return self::success(DeviceRelay::fetchPage($request->page, $request->size, setting: setting()->columns(['id', 'title', 'url', 'created_at', 'updated_at'])));
    }

    /**
     * Получить устройство реле
     * @param int $id Идентификатор устройства
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        return self::success(DeviceRelay::findById($id, setting: setting()->nonNullable()));
    }

    /**
     * Добавить устройство реле
     */
    #[Post]
    public function store(DeviceRelayStoreRequest $request): ResponseInterface
    {
        $relay = new DeviceRelay();

        $relay->title = $request->title;
        $relay->url = $request->url;
        $relay->credential = $request->credential;

        if ($relay->safeInsert()) {
            return self::success($relay->id);
        }

        return self::error('Не удалось добавить устройство реле');
    }

    /**
     * Обновить устройство реле
     */
    #[Put('/{id}')]
    public function update(DeviceRelayUpdateRequest $request): ResponseInterface
    {
        $relay = DeviceRelay::findById($request->id, setting: setting()->nonNullable());

        if ($request->title) {
            $relay->title = $request->title;
        }

        if ($request->url) {
            $relay->url = $request->url;
        }

        if ($request->credential) {
            $relay->credential = $request->credential;
        }

        if ($relay->safeUpdate()) {
            return self::success($relay->id);
        }

        return self::error('Не удалось обновить устройство реле');
    }

    /**
     * Удалить устройство реле
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $relay = DeviceRelay::findById($id, setting: setting()->nonNullable());

        if ($relay->safeDelete()) {
            return self::success();
        }

        return self::error('Не удалось удалить устройство реле');
    }
}
