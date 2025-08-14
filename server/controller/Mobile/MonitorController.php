<?php

declare(strict_types=1);

namespace Selpol\Controller\Mobile;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\MonitorIndexRequest;
use Selpol\Feature\Monitor\MonitorFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;

#[Controller('/mobile/monitor')]
readonly class MonitorController extends MobileRbtController
{
    #[Post]
    public function index(MonitorIndexRequest $request, MonitorFeature $feature): ResponseInterface
    {
        $result = [];

        foreach ($request->ids as $id) {
            $result[$id] = $feature->status($id);
        }

        return user_response(data: $result);
    }
}
