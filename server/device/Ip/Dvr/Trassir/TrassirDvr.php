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
use Selpol\Device\Ip\Dvr\Common\DvrOutput;
use Selpol\Device\Ip\Dvr\Common\DvrStreamer;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Streamer\Stream;
use Selpol\Feature\Streamer\StreamerFeature;
use Selpol\Feature\Streamer\StreamInput;
use Selpol\Feature\Streamer\StreamOutput;
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

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): ?DvrOutput
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP) {
                $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'sub' : 'main');

                if ($rtsp != null)
                    return new DvrOutput($container, $rtsp[0]);
            }
            if ($container === DvrContainer::HLS) {
                $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => $container->value, 'stream' => $arguments['sub'] ? 'sub' : 'main', 'sid' => $this->getSid()]);

                if (array_key_exists('success', $response) && $response['success'])
                    return new DvrOutput($container, $this->server->url . '/hls/' . $response['token'] . '/master.m3u8');
            } else if ($container === DvrContainer::RTC) {
                $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'sub' : 'main');

                if ($rtsp != null) {
                    $stream = new Stream(container(StreamerFeature::class)->random());

                    $stream->source($rtsp[0])->input(StreamInput::RTSP)->output(StreamOutput::RTC);

                    container(StreamerFeature::class)->stream($stream);

                    return new DvrOutput(
                        $container,
                        new DvrStreamer($stream->getServer()->url, $stream->getServer()->id . '-' . $stream->getToken(), $stream->getOutput())
                    );
                }

                return null;
            }
        } else if ($stream === DvrStream::ARCHIVE) {
            $depth = $this->get('/s/archive/timeline', ['channel' => $camera->dvr_stream, 'sid' => $this->getSid()]);

            if (!array_key_exists('success', $depth) || !$depth['success'])
                return null;

            $from = intval(time() - floor($depth['data']['depth'] * 24 * 60 * 60));
            $to = time();

            $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);

            $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'archive_sub' : 'archive');

            if ($rtsp != null) {
                $this->client->send(client_request('GET', $rtsp[0]), $this->clientOption);

                if (!$this->command($identifier, $camera, DvrContainer::RTSP, $stream, DvrCommand::SEEK, ['seek' => $seek, 'token' => $rtsp[1]]))
                    return null;

                if (!$this->command($identifier, $camera, DvrContainer::RTSP, $stream, DvrCommand::PLAY, ['seek' => $seek, 'from' => $from, 'to' => $to, 'token' => $rtsp[1]]))
                    return null;

                $stream = new Stream(container(StreamerFeature::class)->random());

                $stream->source($rtsp[0])->input(StreamInput::RTSP)->output(StreamOutput::RTC);

                container(StreamerFeature::class)->stream($stream);

                return new DvrOutput(
                    DvrContainer::RTC,
                    new DvrArchive(
                        new DvrStreamer($stream->getServer()->url, $stream->getServer()->id . '-' . $stream->getToken(), $stream->getOutput()),
                        $from,
                        $to,
                        $seek,
                        $camera->timezone,
                        $rtsp[1],
                    )
                );
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

    private function getRtspStream(DeviceCamera $camera, string $stream): ?array
    {
        $setting = $this->get('/s/archive/setting', ['sid' => $this->getSid()]);

        if (!array_key_exists('success', $setting) && !$setting['success'])
            return null;

        $rtsp = array_key_exists('rtsp', $setting['data']) ? $setting['data']['rtsp'] : 554;
        $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => DvrContainer::RTSP->value, 'stream' => $stream, 'sid' => $this->getSid()]);

        if (array_key_exists('success', $response) && $response['success'])
            return [(string)uri($this->server->url)->withScheme('rtsp')->withPort($rtsp)->withPath($response['token'] . '/'), $response['token']];

        return null;
    }
}