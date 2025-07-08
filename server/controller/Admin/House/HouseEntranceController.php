<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Throwable;

/**
 * Дом-Вход
 */
#[Controller('/admin/house/{id}/entrance')]
readonly class HouseEntranceController extends AdminRbtController
{
    /**
     * Получить входы для дома
     *
     * @param int $id Идентификатор дома
     * @param HouseFeature $feature
     * @return ResponseInterface
     */
    #[Get]
    public function index(int $id, HouseFeature $feature): ResponseInterface
    {
        $entrances = array_map(
            static function (array $entrance): array {
                try {
                    $entrance['domophoneModel'] = DeviceIntercom::findById($entrance['domophoneId'], setting: setting()->columns(['model'])->nonNullable())->model;
                } catch (Throwable $throwable) {
                    file_logger('error')->error($throwable);
                }

                return $entrance;
            },
            $feature->getEntrances("houseId", $id)
        );

        return self::success($entrances);
    }

    /**
     * Получить общие входы для дома
     *
     * @param int $id Идентификатор дома
     */
    #[Get('/shared')]
    public function shared(int $id, HouseFeature $feature): ResponseInterface
    {
        $entrances = $feature->getSharedEntrances($id);

        return $entrances ? self::success($entrances) : self::error('Не удалось найти общие входы', 404);
    }
}