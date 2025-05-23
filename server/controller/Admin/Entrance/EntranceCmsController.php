<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Entrance;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Entrance\EntranceCmsRequest;
use Selpol\Entity\Model\Entrance\EntranceCms;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSyncCmsTask;

/**
 * Вход-КМС
 */
#[Controller('/admin/entrance/{id}/cms')]
readonly class EntranceCmsController extends AdminRbtController
{
    /**
     * Получить КМС входа
     *
     * @param int $id Идентификатор входа
     */
    #[Get]
    public function show(int $id): ResponseInterface
    {
        $entrance = HouseEntrance::findById($id);

        if (!$entrance) {
            return self::error('Не удалось найти вход', 404);
        }

        return self::success($entrance->cmses);
    }

    /**
     * Изменить КМС входа
     */
    #[Post]
    public function store(EntranceCmsRequest $request): ResponseInterface
    {
        $entrance = HouseEntrance::findById($request->id);

        if (!$entrance) {
            return self::error('Не удалось найти вход', 404);
        }

        $cmses = $entrance->cmses;

        $old = count($cmses);
        $new = count($request->cmses);

        $i = 0;

        for (; $i < $new; $i++) {
            if ($i + 1 < $old) {
                $cmses[$i]->cms = $request->cmses[$i]['cms'];
                $cmses[$i]->dozen = $request->cmses[$i]['dozen'];
                $cmses[$i]->unit = $request->cmses[$i]['unit'];
                $cmses[$i]->apartment = $request->cmses[$i]['apartment'];

                $cmses[$i]->update();
            } else {
                $entranceCms = new EntranceCms();

                $entranceCms->house_entrance_id = $request->id;

                $entranceCms->cms = $request->cmses[$i]['cms'];
                $entranceCms->dozen = $request->cmses[$i]['dozen'];
                $entranceCms->unit = $request->cmses[$i]['unit'];
                $entranceCms->apartment = $request->cmses[$i]['apartment'];

                $entranceCms->insert();
            }
        }

        if ($i + 1 < $old) {
            for (; $i < $old; $i++) {
                $cmses[$i]->delete();
            }
        }

        task(new IntercomSyncCmsTask($request->id))->high()->async();

        return self::success();
    }
}