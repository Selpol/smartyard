<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House\Flat;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\IntercomEntranceTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomFlatKeyTask;

/**
 * Квартира-Вход
 */
#[Controller('/admin/house/flat/{id}/entrance')]
readonly class HouseFlatEntranceController extends AdminRbtController
{
    /**
     * Привязать вход к квартире
     *
     * @param int $id Идентификатор квартиры
     * @param int $entranceId Идентификатор входа
     */
    #[Post('/{entranceId}')]
    public function store(int $id, int $entranceId): ResponseInterface
    {
        $flat = HouseFlat::findById($id);

        if (!$flat) {
            return self::error('Не удалось найти квартиру', 404);
        }

        $entrance = HouseEntrance::findById($entranceId);

        if (!$entrance) {
            return self::error('Не удалось найти вход', 404);
        }

        $entrance->flats()->addWith($flat, ['apartment' => (int)$flat->flat, 'cms_levels' => '']);

        task(new IntercomEntranceTask($entranceId))->high()->async();

        return self::success();
    }
}