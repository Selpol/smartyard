<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Subscriber;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Subscriber\SubscriberCameraRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Камеры абонента
 */
#[Controller('/admin/subscriber/camera')]
readonly class SubscriberCameraController extends AdminRbtController
{
    /**
     * Получить камеры абонента
     */
    #[Get('/{subscriber_id}')]
    public function index(int $subscriber_id): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        return self::success($subscriber->cameras);
    }

    /**
     * Привязать камеру к абоненту
     */
    #[Post('/{subscriber_id}')]
    public function store(SubscriberCameraRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $camera = DeviceCamera::findById($request->camera_id);

        if (!$camera) {
            return self::error('Камера не найдена', 404);
        }

        if ($subscriber->cameras()->add($camera)) {
            return self::success();
        }

        return self::error('Не удалось привязать камеру к абоненту', 400);
    }

    /**
     * Отвязать камеру от абонента
     */
    #[Post('/{subscriber_id}')]
    public function delete(SubscriberCameraRequest $request): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($request->subscriber_id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $camera = DeviceCamera::findById($request->camera_id);

        if (!$camera) {
            return self::error('Камера не найдена', 404);
        }

        if ($subscriber->cameras()->remove($camera)) {
            return self::success();
        }

        return self::error('Не удалось отвязать камеру от абонента', 400);
    }
}