<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House\Flat;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\Key\IntercomFlatKeyTask;

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

    /**
     * Синхронизация ключей квартиры
     * 
     * @param int $id Идентификатор квартиры
     */
    #[Post('/sync')]
    public function sync(int $id): ResponseInterface
    {
        $flat = HouseFlat::findById($id);

        if (!$flat) {
            return self::error('Не удалось найти квартиру', 404);
        }

        task(new IntercomFlatKeyTask($flat->house_flat_id))->high()->dispatch();

        return self::success();
    }
}