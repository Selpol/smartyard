<?php

namespace Selpol\Controller\Api\monitor;

use Fiber;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Monitor\MonitorFeature;

readonly class id extends Api
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     */
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'type' => rule()->in(['ping', 'sip'])
        ]);

        $monitor = container(MonitorFeature::class);

        return self::success((!$validate['type'] || $validate['type'] === 'ping') ? $monitor->ping($validate['_id']) : $monitor->sip($validate['_id']));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public static function POST(array $params): ResponseInterface
    {
        $type = rule()->in(['ping', 'sip'])->onItem('type', $params);

        $result = [];

        if ($type === 'sip') {
            $monitor = container(MonitorFeature::class);

            foreach ($params['ids'] as $id) {
                $id = rule()->id()->onItem('id', ['id' => $id]);

                $result[$id] = $monitor->sip($id);
            }
        } else {
            /** @var Fiber[] $fibers */
            $fibers = [];
            $count = count($params['ids']);

            for ($i = 0; $i < $count; $i++)
                $fibers[$i] = new Fiber(static function (string $url) {
                    $timeout = microtime(true);

                    $socket = stream_socket_client($url, $errno, $errstr, flags: STREAM_CLIENT_ASYNC_CONNECT);

                    if (!$socket)
                        return false;

                    stream_set_blocking($socket, false);

                    while (true) {
                        $w = [$socket];
                        $r = $e = [];

                        if (stream_select($r, $w, $e, 0, 100000)) {
                            if (microtime(true) - $timeout > 1)
                                return false;

                            Fiber::suspend();
                        } else return true;
                    }
                });

            while ($fibers) {
                foreach ($fibers as $i => $fiber) {
                    if (!$fiber->isStarted()) {
                        $id = rule()->id()->onItem('id', ['id' => $params['ids'][$i]]);

                        $intercom = intercom($id);

                        $url = $intercom->uri->getHost();

                        if ($intercom->uri->getPort() === null) {
                            $url .= ':' . match (strtolower($intercom->uri->getScheme())) {
                                    'http' => 80,
                                    'https' => 443,
                                    default => 22
                                };
                        } else $url .= ':' . $intercom->uri->getPort();

                        $fiber->start($url);
                    } else if ($fiber->isTerminated()) {
                        $result[$params['ids'][$i]] = !$fiber->getReturn();

                        unset($fibers[$i]);
                    } else if ($fiber->isSuspended())
                        $fiber->resume();
                }
            }
        }

        return self::success($result);
    }

    public static function index(): array
    {
        return ['GET' => '[Мониторинг] Запросить статус устройства', 'POST' => '[Мониторинг] Запросить статус группы устройств'];
    }
}