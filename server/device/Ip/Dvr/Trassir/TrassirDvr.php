<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Trassir;

use Psr\Http\Message\StreamInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Dvr\Common\DvrArchive;
use Selpol\Device\Ip\Dvr\Common\DvrCommand;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrOnline;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

class TrassirDvr extends DvrDevice
{
    public function __construct(Uri $uri, string $login, #[SensitiveParameter] string $password, DvrModel $model, DvrServer $server)
    {
        parent::__construct($uri, $login, $password, $model, $server);

        $this->clientOption->raw(CURLOPT_SSL_VERIFYHOST, 0)->raw(CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function getCameras(): array
    {
        try {
            $response = $this->get('/channels', ['sid' => $this->getSid()]);

            if (array_key_exists('channels', $response))
                return array_map(static fn(array $channel) => ['id' => $channel['guid'], 'title' => $channel['name']], $response['channels']);

            return [];
        } catch (Throwable) {
            return [];
        }
    }

    public function getCameraId(string $query): ?string
    {
        try {
            $response = $this->get('/channels', ['sid' => $this->getSid()]);

            if (array_key_exists('channels', $response)) {
                $channels = array_values(array_filter($response['channels'], static fn(array $channel) => $channel['name'] === $query));

                if (count($channels) > 0)
                    return $channels[0]['guid'];
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function getSid(): string
    {
        $cache = container(RedisCache::class);

        try {
            $sid = $cache->get('dvr:' . $this->uri . '-' . $this->login);
        } catch (InvalidArgumentException) {
            throw new DeviceException($this, 'Не удалось авторизироваться');
        }

        if (is_null($sid)) {
            $response = $this->get('/login', ['username' => $this->login, 'password' => $this->password]);

            if (array_key_exists('sid', $response)) {
                $sid = $response['sid'];

                try {
                    $cache->set('dvr:' . $this->uri . '-' . $this->login, $sid, 900);
                } catch (InvalidArgumentException) {
                }
            } else throw new DeviceException($this, 'Не удалось авторизироваться');
        }

        return $sid;
    }

    public function capabilities(): array
    {
        return [
            'poster' => true,
            'preview' => false,

            'online' => true,
            'archive' => true,

            'command' => ['play', 'pause', 'seek', 'speed'],
            'speed' => [1, 2, 4]
        ];
    }

    public function identifier(DeviceCamera $camera, int $time, ?int $subscriberId): ?DvrIdentifier
    {
        $start = $time - 3600 * 192;
        $end = $time + 3600 * 3;

        return new DvrIdentifier($this->getToken($camera, $start, $end), $start, $end, $subscriberId);
    }

    public function acquire(?DvrIdentifier $identifier, ?DeviceCamera $camera): int
    {
        return 180;
    }

    public function screenshot(DvrIdentifier $identifier, DeviceCamera $camera, ?int $time): ?StreamInterface
    {
        $request = client_request('GET', $this->uri . '/screenshot/' . $camera->dvr_stream . '?sid=' . $this->getSid() . ($time ? ('&timestamp=' . $time) : ''));

        return $this->client->send($request, $this->clientOption)->getBody();
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->value;
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): DvrArchive|DvrOnline|string|null
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::HLS) {
                $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => $container->value, 'stream' => $arguments['sub'] ? 'sub' : 'main', 'sid' => $this->getSid()]);

                if (array_key_exists('success', $response) && $response['success'])
                    return $this->server->url . '/hls/' . $response['token'] . '/master.m3u8';
            } else if ($container === DvrContainer::RTC) {
                // TODO: Добавить поддержку RTC

                return null;
            }
        } else if ($stream === DvrStream::ARCHIVE) {
            $depth = $this->get('/s/archive/timeline', ['channel' => $camera->dvr_stream, 'sid' => $this->getSid()]);

            if (!array_key_exists('success', $depth) || !$depth['success'])
                return null;

            $from = intval(time() - floor($depth['data']['depth'] * 24 * 60 * 60));
            $to = time();

            $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);

            if ($container === DvrContainer::HLS) {
                $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => $container->value, 'stream' => $arguments['sub'] ? 'archive_sub' : 'archive', 'hw' => $arguments['hw'] ?? false, 'sid' => $this->getSid()]);

                if (array_key_exists('success', $response) && $response['success']) {
                    $this->client->send(client_request('GET', $this->uri . '/hls/' . $response['token'] . '/master.m3u8'), $this->clientOption);

                    if (!$this->command($identifier, $camera, $container, $stream, DvrCommand::SEEK, ['seek' => $seek, 'token' => $response['token']]))
                        return null;

                    if (!$this->command($identifier, $camera, $container, $stream, DvrCommand::PLAY, ['seek' => $seek, 'from' => $from, 'to' => $to, 'token' => $response['token']]))
                        return null;

                    return new DvrArchive($this->server->url . '/hls/' . $response['token'] . '/master.m3u8', $from, $to, $seek, $response['token']);
                }
            }
        }

        return null;
    }

    public function command(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, DvrCommand $command, array $arguments): mixed
    {
        if (!array_key_exists('token', $arguments) || is_null($arguments['token']))
            return null;

        if ($command === DvrCommand::PLAY && array_key_exists('seek', $arguments) && array_key_exists('from', $arguments) && array_key_exists('to', $arguments) && !is_null($arguments['to'])) {
            $response = $this->get('/archive_command', ['command' => 'play', 'start' => $arguments['seek'] ?: $arguments['from'], 'stop' => $arguments['to'], 'speed' => 1, 'token' => $arguments['token'], 'sid' => $this->getSid()]);

            return array_key_exists('success', $response) && $response['success'] == 1;
        } else if ($command === DvrCommand::PAUSE) {
            $response = $this->get('/archive_command', ['command' => 'pause', 'token' => $arguments['token'], 'sid' => $this->getSid()]);

            return array_key_exists('success', $response) && $response['success'] == 1;
        } else if ($command === DvrCommand::SEEK && $arguments['seek']) {
            $response = $this->get('/archive_command', ['command' => 'seek', 'direction' => 0, 'timestamp' => $arguments['seek'], 'token' => $arguments['token'], 'sid' => $this->getSid()]);

            return array_key_exists('success', $response) && $response['success'] == 1;
        } else if ($command === DvrCommand::SPEED && $arguments['speed'] && in_array($arguments['speed'], $this->capabilities()['speed'])) {
            $response = $this->get('/archive_command', ['speed' => $arguments['speed'], 'token' => $arguments['token'], 'sid' => $this->getSid()]);

            return array_key_exists('success', $response) && $response['success'] == 1;
        }

        return null;
    }

    private function getToken(DeviceCamera $camera, int $start, int $end): string
    {
        $salt = bin2hex(openssl_random_pseudo_bytes(16));
        $hash = sha1($camera->dvr_stream . $start . $end . $this->server->token . $salt);

        return $hash . '-' . $salt;
    }
}