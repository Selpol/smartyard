<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\SubscriberDeleteRequest;
use Selpol\Controller\Request\Mobile\SubscriberStoreRequest;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseSubscriber;
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

#[Controller('/mobile/subscriber', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM]])]
readonly class SubscriberController extends MobileRbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get(
        '/{flatId}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId', 'role' => 0],
            BlockFlatMiddleware::class => ['flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function index(int $flatId, HouseFeature $houseFeature): Response
    {
        $flat = HouseFlat::findById($flatId);

        if (!$flat) {
            return user_response(404, message: 'Квартира не найдена');
        }

        $relations = $flat->subscribers()->fetchRelation();
        $subscribers = $flat->subscribers()->fetchAllWithRelation(
            $relations,
            $this->getUser()->getRole() == 0 ? criteria()->equal('role', 0) : null
        );

        $relations = array_reduce($relations, static function (array $previous, array $current): array {
            $previous[$current['house_subscriber_id']] = $current['role'];

            return $previous;
        }, []);

        return user_response(data: array_map(static function (HouseSubscriber $subscriber) use ($relations): array {
            return [
                'subscriberId' => $subscriber->house_subscriber_id,
                'name' => $subscriber->subscriber_name . ' ' . $subscriber->subscriber_patronymic,
                'mobile' => substr($subscriber->id, -4),
                'role' => $relations[$subscriber->house_subscriber_id]
            ];
        }, $subscribers));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
        foreach ($value as $item) {
            if ($item['flatId'] === $id) {
                return $item;
            }
        }

        return null;
    }
}