<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House\Flat;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Квартира-Камера
 */
#[Controller('/admin/house/flat/{id}/camera')]
readonly class HouseFlatCameraController extends AdminRbtController
{
    /**
     * Получить список камер
     * 
     * @param int $id Идентификатор квартиры 
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $flat = HouseFlat::findById($id);

        if (!$flat) {
            return self::error('Не удалось найти квартиру', 404);
        }

        return self::success($flat->cameras);
    }

    /**
     * Привязать камеру к квартире
     * 
     * @param int $id Идентификатор квартиры 
     * @param int $camera_id Идентификатор камеры
     */
    #[Post('/{camera_id}')]
    public function store(int $id, int $camera_id): ResponseInterface
    {
        $flat = HouseFlat::findById($id);

        if (!$flat) {
            return self::error('Не удалось найти квартиру', 404);
        }

        $camera = DeviceCamera::findById($camera_id);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        if (!$flat->cameras()->add($camera)) {
            return self::error('Не удалось привязать камеру', 400);
        }

        return self::success();
    }

    /**
     * Отвязать камеру от квартиры
     * 
     * @param int $id Идентификатор квартиры 
     * @param int $camera_id Идентификатор камеры
     */
    #[Delete('/{camera_id}')]
    public function delete(int $id, int $camera_id): ResponseInterface
    {
        $flat = HouseFlat::findById($id);

        if (!$flat) {
            return self::error('Не удалось найти квартиру', 404);
        }

        $camera = DeviceCamera::findById($camera_id);

        if (!$camera) {
            return self::error('Не удалось найти камеру', 404);
        }

        if (!$flat->cameras()->remove($camera)) {
            return self::error('Не удалось отвязать камеру от квартиры', 400);
        }

        return self::success();
    }
}