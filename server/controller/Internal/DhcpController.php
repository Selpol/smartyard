<?php

declare(strict_types=1);

namespace Selpol\Controller\Internal;

use Selpol\Controller\Request\Internal\DhcpRequest;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Route\RouteController;

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
    public function index(DhcpRequest $request): Response
    {
        file_logger('dhcp')->debug('event', ['ip' => $request->ip, 'mac' => $request->mac, 'host' => $request->host, 'server' => $request->server]);

        return response(200);
    }
}
