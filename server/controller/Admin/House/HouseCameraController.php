<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Дом-Камера
 */
#[Controller('/admin/house/{id}/camera')]
readonly class HouseCameraController extends AdminRbtController
{
    /**
     * Получить список камер
     * 
     * @param int $id Идентификатор дома 
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        return self::success($house->cameras);
    }

    /**
     * Привязать камеру к дому
     * 
     * @param int $id Идентификатор дома 
     * @param int $camera_id Идентификатор камеры
     */
    #[Post('/{camera_id}')]
    public function store(int $id, int $camera_id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        $camera = DeviceCamera::findById($camera_id);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        if (!$house->cameras()->add($camera)) {
            return self::error('Не удалось привязать камеру', 400);
        }

        return self::success();
    }

    /**
     * Отвязать камеру от дома
     * 
     * @param int $id Идентификатор дома 
     * @param int $camera_id Идентификатор камеры
     */
    #[Delete('/{camera_id}')]
    public function delete(int $id, int $camera_id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти ljv', 404);
        }

        $camera = DeviceCamera::findById($camera_id);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        if (!$house->cameras()->remove($camera)) {
            return self::error('Не удалось отвязать камеру от дома', 400);
        }

        return self::success();
    }
}