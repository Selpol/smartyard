<?php

namespace Selpol\Controller\Internal;

use Psr\Http\Message\ResponseInterface;
use Selpol\Cache\RedisCache;
use Selpol\Controller\RbtController;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Middleware\RateLimitMiddleware;
use Selpol\Service\TaskService;
use Throwable;

#[Controller('/internal/zabbix')]
readonly class ZabbixController extends RbtController
{
    #[Get(includes: [RateLimitMiddleware::class => ['count' => 1, 'ttl' => 30, 'request' => true]])]
    public function index(RedisCache $cache, TaskService $task): ResponseInterface
    {
        try {
            if (!CoreVar::getRepository()->findByName('database.version'))
                return user_response(500, message: 'Версия миграций базы данных не определена');
        } catch (Throwable) {
            return user_response(500, message: 'База данных не доступна');
        }

        try {
            if (!$cache->set('zabbix', 1, 5))
                return user_response(500, message: 'Не удалось закэшировать значение');

            if ($cache->get('zabbix') != 1)
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