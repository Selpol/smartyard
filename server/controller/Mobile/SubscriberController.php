<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\SubscriberDeleteRequest;
use Selpol\Controller\Request\Mobile\SubscriberStoreRequest;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/mobile/subscriber', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM]])]
readonly class SubscriberController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get(
        '/{flatId}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId'],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function index(int $flatId, HouseFeature $houseFeature): Response
    {
        $subscribers = $houseFeature->getSubscribers('flatId', $flatId);

        return user_response(
            data: array_map(
                fn(array $subscriber): array => [
                    'subscriberId' => $subscriber['subscriberId'],
                    'name' => $subscriber['subscriberName'] . ' ' . $subscriber['subscriberPatronymic'],
                    'mobile' => substr($subscriber['mobile'], -4),
                    'role' => @($this->getFlat($subscriber['flats'], $flatId)['role'])
                ],
                $subscribers
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Post(
        '/{flatId}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId', 'role' => 0],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function store(SubscriberStoreRequest $request, int $flatId, HouseFeature $houseFeature): Response
    {
        $subscribers = $houseFeature->getSubscribers('mobile', $request->mobile);

        if ($subscribers === [] || $subscribers === false || count($subscribers) === 0) {
            $id = $houseFeature->addSubscriber($request->mobile, 'Имя', 'Отчество', flatId: $flatId);

            if ($id === 0 || $id === false) {
                return user_response(400, message: 'Неудалось зарегестрировать жителя');
            }

            $subscribers = $houseFeature->getSubscribers('id', $id);
        }

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flatId);

        if ($subscriberFlat !== null && $subscriberFlat !== []) {
            return user_response(400, message: 'Житель уже добавлен');
        }

        if (!$houseFeature->addSubscriberToFlat($flatId, $subscriber['subscriberId'], 1)) {
            return user_response(400, message: 'Житель не был добавлен');
        }

        return user_response();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Delete(
        '/{flatId}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId', 'role' => 0],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function delete(SubscriberDeleteRequest $request, int $flatId, HouseFeature $houseFeature): Response
    {
        $subscribers = $houseFeature->getSubscribers('id', $request->subscriberId);

        if ($subscribers === [] || $subscribers === false || count($subscribers) === 0) {
            return user_response(404, message: 'Житель не зарегестрирован');
        }

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flatId);

        if ($subscriberFlat === null || $subscriberFlat === []) {
            return user_response(400, message: 'Житель не заселен в данной квартире');
        }

        if ($subscriberFlat['role'] == 0) {
            return user_response(403, message: 'Житель является владельцем квартиры');
        }

        if ($houseFeature->removeSubscriberFromFlat($flatId, $subscriber['subscriberId']) === false || $houseFeature->removeSubscriberFromFlat($flatId, $subscriber['subscriberId']) === 0) {
            return user_response(400, message: 'Житель не был удален');
        }

        return user_response();
    }

    private function getFlat(array $value, int $id): ?array
    {
        foreach ($value as $item)
            if ($item['flatId'] === $id) {
                return $item;
            }

        return null;
    }
}