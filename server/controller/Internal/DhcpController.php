<?php

declare(strict_types=1);

namespace Selpol\Controller\Internal;

use Selpol\Controller\Request\Internal\DhcpRequest;
use Selpol\Device\Exception\DeviceException;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Intercom\IntercomApproved;
use Selpol\Feature\Intercom\IntercomFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Route\RouteController;
use Selpol\Service\DeviceService;
use Throwable;

/**
 * DHCP События
 */
#[Controller('/internal/dhcp')]
readonly class DhcpController extends RouteController
{
    /**
     * Получение новой lease
     */
    #[Post]
    public function index(DhcpRequest $request, IntercomFeature $feature): Response
    {
        file_logger('dhcp')->debug('event', ['ip' => $request->ip, 'mac' => $request->mac, 'host' => $request->host, 'server' => $request->server]);

        if (DeviceIntercom::fetch(criteria()->equal('ip', $request->ip)) != null) {
            return response(200);
        }

        try {
            $intercom = new DeviceIntercom();

            $intercom->house_domophone_id = 0;
            $intercom->model = 'auto';
            $intercom->url = 'http://' . $request->ip;
            $intercom->credentials = '';

            $intercom->ip = $request->ip;

            $intercom->config = '';

            file_logger('dhcp')->debug('intercom', [$intercom]);

            try {
                $device = container(DeviceService::class)->intercomByEntity($intercom);

                if ($device) {
                    $model = $device->specification();
                    $password = $device->password;
                } else {
                    $model = 'auto';
                    $password = '';
                }
            } catch (DeviceException $exception) {
                $model = 'auto';
                $password = '';
            }

            $approved = new IntercomApproved(
                $request->ip,
                $request->mac,
                $request->host,
                $request->server,
                $model,
                $password,
                86400 * 7
            );

            file_logger('dhcp')->debug('approved', [$approved]);

            $feature->addApproved($approved);
        } catch (Throwable $throwable) {
            file_logger('dhcp')->error($throwable);
        }

        return response(200);
    }
}
