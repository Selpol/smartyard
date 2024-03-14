<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Trassir;

use Psr\Http\Message\StreamInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Dvr\Common\DvrArchive;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
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
            'preview' => true,

            'online' => true,
            'archive' => true,

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
        $request = client_request('GET', $this->uri . '/screenshot/' . $camera->dvr_stream . '?sid=' . $this->getSid() . ($time ? ('&timestamp=' . ($time * 1000)) : ''));

        return $this->client->send($request, $this->clientOption)->getBody();
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        if ($arguments['time'])
            return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->value . '?time=' . $arguments['time'];

        return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->value;
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): DvrArchive|string|null
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP)
                return null; // TODO: Добавить поддержку RTSP, с кэшированием опций /s/archive/timeline?channel=?&sip=?
            else if ($container === DvrContainer::HLS) {
                $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => $container->value, 'stream' => $arguments['sub'] ? 'sub' : 'main', 'sid' => $this->getSid()]);

                if (array_key_exists('success', $response) && $response['success'])
                    return $this->server->url . '/hls/' . $response['token'] . '/master.m3u8';

                return null;
            }
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