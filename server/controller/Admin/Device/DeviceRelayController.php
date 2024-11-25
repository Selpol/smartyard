<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Device;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Device\DeviceRelayFlapRequest;
use Selpol\Controller\Request\Admin\Device\DeviceRelayIndexRequest;
use Selpol\Controller\Request\Admin\Device\DeviceRelayStoreRequest;
use Selpol\Controller\Request\Admin\Device\DeviceRelayUpdateRequest;
use Selpol\Entity\Model\Device\DeviceRelay;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
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
        $relay->invert = $request->invert;

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

        if (!is_null($request->invert)) {
            $relay->invert = $request->invert;
        }

        if ($relay->safeUpdate()) {
            return self::success($relay->id);
        }

        return self::error('Не удалось обновить устройство реле');
    }

    /**
     * Флапнуть устройством реле
     */
    #[Get('/flap/{id}')]
    public function flap(DeviceRelayFlapRequest $request, Client $client): ResponseInterface
    {
        $relay = DeviceRelay::findById($request->id, setting: setting()->nonNullable());

        $option = (new ClientOption())->basic($relay->credential);

        $response = $client->send(
            request('PUT', uri($relay->url)->withPath('/api/v1/relay'))
                ->withBody(stream(['value' => $relay->invert])),
            $option
        );

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        sleep($request->sleep);

        $response = $client->send(
            request('PUT', uri($relay->url)->withPath('/api/v1/relay'))
                ->withBody(stream(['value' => !$relay->invert])),
            $option
        );

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        return self::success();
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