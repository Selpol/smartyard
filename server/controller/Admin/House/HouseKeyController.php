<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\House\HouseKeyRequest;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Throwable;

/**
 * Дом-Ключи
 */
#[Controller('/admin/house/{id}/key')]
readonly class HouseKeyController extends AdminRbtController
{
    /**
     * Загрузить ключи в дом
     */
    #[Post]
    public function store(HouseKeyRequest $request): ResponseInterface
    {
        foreach ($request->keys as $key) {
            try {
                $houseKey = new HouseKey();

                $houseKey->rfid = $key['rfId'];
                $houseKey->access_type = 2;
                $houseKey->access_to = $key['accessTo'];
                $houseKey->comments = array_key_exists('comment', $key) ? $key['comment'] : '';

                $houseKey->insert();
            } catch (Throwable) {

            }
        }

        $task = task(new IntercomKeysKeyTask($request->id, $request->keys));

        if (count($request->keys) < 25) {
            try {
                $task->sync();
            } catch (Throwable) {
                return self::error('Не удалось загрузить ключи', 400);
            }
        } else {
            $task->high()->dispatch();
        }

        return self::success();
    }
}