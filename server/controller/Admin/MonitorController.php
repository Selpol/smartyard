<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Fiber;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\MonitorIntercomRequest;
use Selpol\Feature\Monitor\MonitorFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

#[Controller('/admin/monitor')]
readonly class MonitorController extends AdminRbtController
{
    /**
     * @throws \Throwable
     */
    #[Get]
    public function index(MonitorIntercomRequest $request, MonitorFeature $feature): ResponseInterface
    {
        $result = [];

        if ($request->type === 'sip') {
            foreach ($request->ids as $id) {
                $result[$id] = $feature->sip($id);
            }
        } else if ($request->type === 'ping') {
            /** @var Fiber[] $fibers */
            $fibers = [];
            $count = count($request->ids);

            for ($i = 0; $i < $count; ++$i)
                $fibers[$i] = new Fiber(static function (string $url): bool {
                    $timeout = microtime(true);

                    $socket = stream_socket_client($url, $errno, $errstr, flags: STREAM_CLIENT_ASYNC_CONNECT);

                    if (!$socket) {
                        return false;
                    }

                    stream_set_blocking($socket, false);

                    while (true) {
                        $w = [$socket];
                        $r = [];
                        $e = [];

                        if (stream_select($r, $w, $e, 0, 100000)) {
                            if (microtime(true) - $timeout > 1) {
                                return false;
                            }

                            Fiber::suspend();
                        } else {
                            return true;
                        }
                    }
                });

            while ($fibers) {
                foreach ($fibers as $i => $fiber) {
                    if (!$fiber->isStarted()) {
                        $intercom = intercom($request->ids[$i]);
                        $url = $intercom->uri->getHost();

                        if ($intercom->uri->getPort() === null) {
                            $url .= ':' . match (strtolower($intercom->uri->getScheme())) {
                                    'http' => 80,
                                    'https' => 443,
                                    default => 22
                                };
                        } else {
                            $url .= ':' . $intercom->uri->getPort();
                        }

                        $fiber->start($url);
                    } elseif ($fiber->isTerminated()) {
                        $result[$request->ids[$i]] = !$fiber->getReturn();
                        unset($fibers[$i]);
                    } elseif ($fiber->isSuspended()) {
                        $fiber->resume();
                    }
                }
            }
        }

        return self::success($result);
    }

    public static function scopes(): array
    {
        return [
            'monitor-index-get' => '[Мониторинг] Запросить статус устройств'
        ];
    }
}