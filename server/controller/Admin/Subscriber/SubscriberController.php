<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Subscriber;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Subscriber\SubscriberStoreRequest;
use Selpol\Controller\Request\Admin\Subscriber\SubscriberUpdateRequest;
use Selpol\Controller\Request\Admin\SubscriberRequest;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Throwable;

/**
 * Абоненты
 */
#[Controller('/admin/subscriber')]
readonly class SubscriberController extends AdminRbtController
{
    /**
     * Получить список абонентов
     */
    #[Get]
    public function index(SubscriberRequest $request, AuthService $service): ResponseInterface
    {
        if ($request->flat_id) {
            $flat = HouseFlat::findById($request->flat_id);

            if (!$flat) {
                return self::error('Не удалось найти квартиру', 404);
            }

            return self::success($flat->subscribers);
        }

        $criteria = criteria()
            ->in('house_subscriber_id', $request->ids)
            ->like('subscriber_name', $request->name)
            ->like('subscriber_patronymic', $request->patronymic)
            ->equal('id', $request->mobile)
            ->equal('platform', $request->platform)
            ->equal('push_token_type', $request->push_token_type)
            ->asc('house_subscriber_id');

        $page = HouseSubscriber::fetchPage($request->page, $request->size, $criteria);

        if ($service->checkScope('mobile-mask')) {
            return self::success($page);
        }

        return self::success(new EntityPage(array_map(static function (HouseSubscriber $subscriber): HouseSubscriber {
            $subscriber->id = mobile_mask($subscriber->id);

            return $subscriber;
        }, $page->getData()), $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    /**
     * Получить абонента
     * 
     * @param int $house_subscriber_id Идентификатор абонента 
     */
    #[Get('/{house_subscriber_id}')]
    public function show(int $house_subscriber_id, AuthService $authService): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 400);
        }

        if (!$authService->checkScope('mobile-mask')) {
            $subscriber->id = mobile_mask($subscriber->id);
        }

        return self::success($subscriber);
    }

    /**
     * Создать нового абонента
     */
    #[Post]
    public function store(SubscriberStoreRequest $request, OauthFeature $feature): ResponseInterface
    {
        $subscriber = HouseSubscriber::fetch(criteria()->equal('id', $request->id));

        if ($subscriber) {
            return self::success($subscriber->house_subscriber_id);
        }

        $subscriber = new HouseSubscriber();

        $subscriber->id = $request->id;

        $subscriber->subscriber_name = $request->subscriber_name;
        $subscriber->subscriber_patronymic = $request->subscriber_patronymic;

        try {
            $subscriber->aud_jti = $feature->register($request->id);
        } catch (Throwable $throwable) {
            file_logger('subscriber')->error($throwable);
        }

        $subscriber->insert();

        return self::success($subscriber->house_subscriber_id);
    }

    /**
     * Обновить абонента
     */
    #[Put('/{house_subscriber_id}')]
    public function update(SubscriberUpdateRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 400);
        }

        $subscriber->subscriber_name = $request->subscriber_name;
        $subscriber->subscriber_patronymic = $request->subscriber_patronymic;

        $subscriber->voip_enabled = $request->voip_enabled;

        $subscriber->update();

        return self::success();
    }

    /**
     * Удалить абонента
     * 
     * @param int $house_subscriber_id Идентификатор абонента
     */
    #[Delete('/{house_subscriber_id}')]
    public function delete(int $house_subscriber_id): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($house_subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 400);
        }

        $subscriber->delete();

        return self::success();
    }
}
