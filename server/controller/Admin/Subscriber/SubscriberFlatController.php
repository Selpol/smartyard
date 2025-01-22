<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Subscriber;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Квартиры абонента
 */
#[Controller('/admin/subscriber/{id}/flat')]
readonly class SubscriberFlatController extends AdminRbtController
{
    /**
     * Получить квартиры абонента
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $subscriber = HouseSubscriber::findById($id);

        if (!$subscriber) {
            return self::error('Абонент не найден', 404);
        }

        $relations = $subscriber->flats()->fetchRelation();
        $flats = $subscriber->flats()->fetchAllWithRelation($relations);

        $relations = array_reduce($relations, static function (array $previous, array $current): array {
            $previous[$current['house_flat_id']] = $current['role'];

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            $flat->__set('role', $relations[$flat->house_flat_id]);

            $house = $flat->house()->fetch(setting: setting()->columns(['house_full']));

            if ($house) {
                $flat->__set('house_full', $house->house_full);
            }
        }

        return self::success($flats);
    }
}