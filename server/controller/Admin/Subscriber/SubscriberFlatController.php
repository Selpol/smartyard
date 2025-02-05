<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Subscriber;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Subscriber\SubscriberFlatRequest;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Квартиры абонента
 */
#[Controller('/admin/subscriber/flat/{house_subscriber_id}')]
readonly class SubscriberFlatController extends AdminRbtController
{
    /**
     * Получить квартиры абонента
     * 
     * @param int $house_subscriber_id Идентификатор абонента 
     */
    #[Get]
    public function index(int $house_subscriber_id): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $relations = $subscriber->flats()->fetchRelation();
        $flats = $subscriber->flats()->fetchAllWithRelation($relations);

        $relations = array_reduce($relations, static function (array $previous, array $current): array {
            $previous[$current['house_flat_id']] = $current['role'];

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            $flat->__set('role', $relations[$flat->house_flat_id]);

            $house = $flat->house()->fetch(setting: setting()->columns(['house_full']));

            if ($house) {
                $flat->__set('house_full', $house->house_full);
            }
        }

        return self::success($flats);
    }

    /**
     * Привязать квартиру к абоненту
     */
    #[Post('/{flat_id}')]
    public function store(SubscriberFlatRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $flat = HouseFlat::findById($request->flat_id);

        if (!$flat) {
            return self::error('Квартира не найдена', 404);
        }

        if ($subscriber->flats()->addWith($flat, ['role' => $request->role])) {
            return self::success();
        }

        return self::error('Не удалось привязать квартиру к абоненту', 400);
    }

    /**
     * Обновить привязку абонента к квартире
     */
    #[Put('/{flat_id}')]
    public function update(SubscriberFlatRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $flat = HouseFlat::findById($request->flat_id);

        if (!$flat) {
            return self::error('Квартира не найдена', 404);
        }

        if (!$subscriber->flats()->has($flat)) {
            return self::error('Квартира не привязана к абоненту', 404);
        }

        $subscriber->flats()->remove($flat);

        if ($subscriber->flats()->addWith($flat, ['role' => $request->role])) {
            return self::success();
        }

        return self::error('Не удалось обновить привязку квартиры к абоненту', 400);
    }

    /**
     * Отвязать квартиру от абонента
     */
    #[Delete('/{flat_id}')]
    public function delete(SubscriberFlatRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $flat = HouseFlat::findById($request->flat_id);

        if (!$flat) {
            return self::error('Квартира не найдена', 404);
        }

        if ($subscriber->flats()->remove($flat)) {
            return self::success();
        }

        return self::error('Не удалось отвязать квартиру от абонента', 400);
    }
}