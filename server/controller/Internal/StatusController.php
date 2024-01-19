<?php

namespace Selpol\Controller\Internal;

use Psr\Http\Message\ResponseInterface;
use Selpol\Cache\RedisCache;
use Selpol\Controller\RbtController;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\TaskService;
use Throwable;

#[Controller('/internal/status')]
readonly class StatusController extends RbtController
{
    #[Get]
    public function index(RedisCache $cache, TaskService $task): ResponseInterface
    {
        try {
            if (!CoreVar::getRepository()->findByName('database.version'))
                return user_response(500, message: 'Версия миграций базы данных не определена');
        } catch (Throwable) {
            return user_response(500, message: 'База данных не доступна');
        }

        try {
            if (!$cache->set('status', 1, 5))
                return user_response(500, message: 'Не удалось закэшировать значение');

            if ($cache->get('status') != 1)
                return user_response(500, message: 'Не удалось получить закэшированное значение');
        } catch (Throwable) {
            return user_response(500, message: 'Redis сервер не доступен');
        }

        try {
            $task->connect();
            $task->close();
        } catch (Throwable) {
            return user_response(500, message: 'Amqp сервер не доступен');
        }

        return user_response();
    }
}