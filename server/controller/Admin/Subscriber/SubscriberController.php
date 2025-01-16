<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Subscriber;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\SubscriberRequest;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\AuthService;

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
        $criteria = criteria()
            ->in('house_subscriber_id', $request->ids)
            ->like('subscriber_name', $request->name)
            ->like('subscriber_patronymic', $request->patronymic)
            ->equal('id', $request->mobile)
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
     * @param int $id Идентификатор абонента 
     */
    #[Get('/{id}')]
    public function show(int $id, AuthService $authService): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 400);
        }

        if (!$authService->checkScope('mobile-mask')) {
            $subscriber->id = mobile_mask($subscriber->id);
        }

        return self::success($subscriber);
    }
}