<?php

namespace Selpol\Controller\Mobile;

use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\FlatUpdateRequest;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Service\DatabaseService;

#[Controller(
    '/mobile/flat',
    includes: [
        BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL],
        FlatMiddleware::class => ['flat' => 'id'],
        BlockFlatMiddleware::class => ['flat' => 'id', 'services' => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]],
    ]
)]
readonly class FlatController extends MobileRbtController
{
    #[Get('/{id}')]
    public function show(int $id): Response
    {
        $flats = $this->getUser()->getOriginalValue()['flats'];

        foreach ($flats as $flat) {
            if ($flat['flatId'] === $id) {
                return user_response(data: ['call' => $flat['call']]);
            }
        }

        return user_response(404, message: 'Не удалось найти квартиру');
    }

    #[Post('/{id}')]
    public function update(FlatUpdateRequest $request, DatabaseService $service): Response
    {
        $flats = $this->getUser()->getOriginalValue()['flats'];

        foreach ($flats as $flat) {
            if ($flat['flatId'] === $request->id) {
                $service->statement('UPDATE houses_flats_subscribers SET call = :call WHERE house_flat_id = :house_flat_id AND house_subscriber_id = :house_subscriber_id')
                    ->execute(['call' => $request->call, 'house_flat_id' => $request->id, 'house_subscriber_id' => $this->getUser()->getIdentifier()]);

                return user_response();
            }
        }

        return user_response(404, message: 'Не удалось найти квартиру');
    }
}