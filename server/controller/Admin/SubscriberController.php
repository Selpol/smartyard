<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\SubscriberRequest;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

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
    public function index(SubscriberRequest $request): ResponseInterface
    {
        return self::success(HouseSubscriber::fetchPage($request->page, $request->size, criteria()->equal('subscriber_name', $request->name)->equal('subscriber_patronymic', $request->patronymic)->equal('id', $request->id)));
    }
}