<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House\Flat;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Квартира-Ключ
 */
#[Controller('/admin/house/flat/{id}/key')]
readonly class HouseFlatKeyController extends AdminRbtController
{
    /**
     * Получить список ключей
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

        return self::success($flat->keys);
    }
}