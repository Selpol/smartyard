<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Device\Setting;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
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
#[Controller('/admin/device/relay-setting')]
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

        $option = (new ClientOption())->basic(base64_decode($relay->credential));

        $response = $client->send(request('GET', uri($relay->url)->withPath('/api/v1/setting')), $option);

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        $body = json_decode($response->getBody()->getContents(), true);

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

        $option = (new ClientOption())->basic(base64_decode($relay->credential));

        $response = $client->send(request('PUT', uri($relay->url)->withPath('/api/v1/setting'))->withBody(stream([
            'authentication' => $request->authentication,

            'open_duration' => $request->open_duration,

            'ping_address' => $request->ping_address,
            'ping_timeout' => $request->ping_timeout
        ])), $option);

        if ($response->getStatusCode() != 200) {
            return self::error($response->getReasonPhrase(), 400);
        }

        return self::success();
    }
}
