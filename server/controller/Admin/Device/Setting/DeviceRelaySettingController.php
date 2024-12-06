<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Device\Setting;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Device\Setting\DeviceRelaySettingModeRequest;
use Selpol\Controller\Request\Admin\Device\Setting\DeviceRelaySettingFlapRequest;
use Selpol\Controller\Request\Admin\Device\Setting\DeviceRelaySettingUpdateRequest;
use Selpol\Entity\Model\Device\DeviceRelay;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Устройство-реле настройки
 */
#[Controller('/admin/device/relay/setting')]
readonly class DeviceRelaySettingController extends AdminRbtController
{
    /**
     * Получить настройки устройства
     * @param int $id Идентификатор устройства
     */
    #[Get('/{id}')]
    public function index(int $id, Client $client): ResponseInterface
    {
        $relay = DeviceRelay::findById($id, setting: setting()->nonNullable());

        $option = (new ClientOption())->basic($relay->credential);

        $response = $client->send(request('GET', uri($relay->url)->withPath('/api/v1/setting')), $option);

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        $body = json_decode($response->getBody()->getContents());

        if (array_key_exists('data', $body)) {
            return self::success($body['data']);
        } else if (array_key_exists('message', $body)) {
            return self::error($body['message']);
        }

        return self::error();
    }

    /**
     * Обновить настройки устройства
     */
    #[Put('/{id}')]
    public function update(DeviceRelaySettingUpdateRequest $request, Client $client): ResponseInterface
    {
        $relay = DeviceRelay::findById($request->id, setting: setting()->nonNullable());

        $option = (new ClientOption())->basic($relay->credential);

        $response = $client->send(request('PUT', uri($relay->url)->withPath('/api/v1/setting'))->withBody(stream([
            'pin' => $request->pin,
            'invert' => $request->invert,
            'authentication' => $request->authentication,
            'ping_address' => $request->ping_address,
            'ping_timeout' => $request->ping_timeout
        ])), $option);

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        return self::success();
    }

    /**
     * Флапнуть устройством реле
     */
    #[Get('/flap/{id}')]
    public function flap(DeviceRelaySettingFlapRequest $request, Client $client): ResponseInterface
    {
        $relay = DeviceRelay::findById($request->id, setting: setting()->nonNullable());

        $option = (new ClientOption())->basic($relay->credential);

        $response = $client->send(
            request('PUT', uri($relay->url)->withPath('/api/v1/relay'))
                ->withBody(stream(['value' => true])),
            $option
        );

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        sleep($request->sleep);

        $response = $client->send(
            request('PUT', uri($relay->url)->withPath('/api/v1/relay'))
                ->withBody(stream(['value' => false])),
            $option
        );

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        return self::success();
    }

    /**
     * Установить режим реле
     */
    #[Get('/mode/{id}')]
    public function mode(DeviceRelaySettingModeRequest $request, Client $client): ResponseInterface
    {
        $relay = DeviceRelay::findById($request->id, setting: setting()->nonNullable());

        $option = (new ClientOption())->basic($relay->credential);

        $response = $client->send(
            request('PUT', uri($relay->url)->withPath('/api/v1/relay'))
                ->withBody(stream(['value' => $request->value])),
            $option
        );

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        return self::success();
    }
}
