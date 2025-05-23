<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Entrance;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Entrance\EntranceFlatRequest;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\IntercomEntranceTask;

/**
 * Вход-квартира
 */
#[Controller('/admin/entrance/{id}/flat')]
readonly class EntranceFlatController extends AdminRbtController
{
    /**
     * Привязать вход к квартирам
     */
    #[Post]
    public function store(EntranceFlatRequest $request, HouseFeature $feature): ResponseInterface
    {
        $entrance = HouseEntrance::findById($request->id);

        foreach ($request->flats as $flat) {
            $feature->addEntranceToFlat($entrance->house_entrance_id, $flat['flatId'], $flat['apartment']);
        }

        task(new IntercomEntranceTask($entrance->house_entrance_id))->high()->async();

        return self::success();
    }
}