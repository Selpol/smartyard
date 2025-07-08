<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Дом
 */
#[Controller('/admin/house/{id}')]
readonly class HouseController extends AdminRbtController
{
    /**
     * Получить дом
     *
     * @param int $id Идентификатор дома
     */
    #[Get]
    public function index(int $id, HouseFeature $feature): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дома', 404);
        }

        $flats = $feature->getFlats("houseId", $id, true);

        if ($flats) {
            usort($flats, static fn(array $a, array $b): int => $a['flat'] > $b['flat'] ? 1 : -1);
        }

        return self::success([
            'house' => $house,
            'flats' => $flats
        ]);
    }
}