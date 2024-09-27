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
    public function __construct(Uri $uri, string $login, #[SensitiveParameter] string $password, DvrModel $model, DvrServer $server, ?int $id = null)
    {
        parent::__construct($uri, $login, $password, $model, $server, $id);

        $this->clientOption->raw(CURLOPT_SSL_VERIFYHOST, 0)->raw(CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function getCameras(): array
    {
        try {
            $response = $this->get('/channels', ['sid' => $this->getSid()]);

            if (array_key_exists('channels', $response)) {
                return array_map(static fn(array $channel): array => ['id' => $channel['guid'], 'title' => $channel['name']], $response['channels']);
            }

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
                $channels = array_values(array_filter($response['channels'], static fn(array $channel): bool => $channel['name'] === $query));

                if ($channels !== []) {
                    return $channels[0]['guid'];
                }
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
            } else {
                throw new DeviceException($this, 'Не удалось авторизироваться');
            }
        }

        return $sid;
    }

    private function getSetting(): ?array
    {
        $cache = container(RedisCache::class);

        try {
            $setting = $cache->get('dvr:' . $this->uri . ':setting');
        } catch (Throwable) {
            $setting = null;
        }

        if (is_null($setting)) {
            $response = $this->get('/s/archive/setting', ['sid' => $this->getSid()]);

            if (array_key_exists('success', $response) && $response['success']) {
                $setting = $response['data'];

                try {
                    $cache->set('dvr:' . $this->uri . ':setting', $setting, 900);
                } catch (Throwable) {
                }
            }
        }

        return $setting;
    }

    public function capabilities(): array
    {
        return [
            'poster' => true,
            'preview' => true,

            'online' => true,
            'archive' => true,

            'command' => [DvrCommand::PLAY->value, DvrCommand::PAUSE->value, DvrCommand::SEEK->value, DvrCommand::SPEED->value, DvrCommand::PING->value, DvrCommand::STATUS->value],
            'speed' => [1, 2, 4]
        ];
    }

    public function identifier(DeviceCamera $camera, int $time, ?int $subscriberId): ?DvrIdentifier
    {
        $start = $time - 300;
        $end = $time + 3600;

        return new DvrIdentifier($camera->camera_id, $camera->dvr_server_id, $start, $end, $subscriberId);
    }

    public function screenshot(DvrIdentifier $identifier, DeviceCamera $camera, ?int $time): ?StreamInterface
    {
        $request = client_request('GET', $this->uri . '/screenshot/' . $camera->dvr_stream . '?figures=0&sid=' . $this->getSid() . ($time !== null && $time !== 0 ? ('&timestamp=' . $time) : ''));

        return $this->client->send($request, $this->clientOption)->getBody();
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->toToken();
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): ?DvrOutput
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP) {
                $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'sub' : 'main');

                if ($rtsp == null) {
                    return null;
                }

                return new DvrOutput($container, $rtsp[0]);
            }

            if ($container === DvrContainer::HLS) {
                $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => $container->value, 'stream' => $arguments['sub'] ? 'sub' : 'main', 'sid' => $this->getSid()]);

                if (array_key_exists('success', $response) && $response['success']) {
                    return new DvrOutput($container, $this->server->url . '/hls/' . $response['token'] . '/master.m3u8');
                }

                return null;
            }

            if ($container === DvrContainer::STREAMER_RTC || $container === DvrContainer::STREAMER_RTSP) {
                $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'sub' : 'main');

                if ($rtsp == null) {
                    return null;
                }

                $stream = new Stream(container(StreamerFeature::class)->random());

                $stream->source($rtsp[0])->input(StreamInput::RTSP)->output($container == DvrContainer::STREAMER_RTC ? StreamOutput::RTC : StreamOutput::RTSP);

                container(StreamerFeature::class)->stream($stream);

                return new DvrOutput(
                    $container,
                    new DvrStreamer($stream->getServer()->url, $stream->getServer()->id . '-' . $stream->getToken(), $stream->getOutput())
                );
            }
        } elseif ($stream === DvrStream::ARCHIVE) {
            $depth = $this->get('/s/archive/timeline', ['channel' => $camera->dvr_stream, 'sid' => $this->getSid()]);

            if (!array_key_exists('success', $depth) || !$depth['success']) {
                return null;
            }

            $from = intval(time() - floor($depth['data']['depth'] * 24 * 60 * 60));
            $to = time();
            $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);
            $rtsp = $this->getRtspStream($camera, $arguments['sub'] ? 'archive_sub' : 'archive');

            if ($rtsp != null) {
                $stream = new Stream(container(StreamerFeature::class)->random());

                $stream->source($rtsp[0])->input(StreamInput::RTSP)->output($container == DvrContainer::STREAMER_RTC ? StreamOutput::RTC : StreamOutput::RTSP);

                container(StreamerFeature::class)->stream($stream);

                return new DvrOutput(
                    $container,
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

    public function timeline(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?array
    {
        if (!array_key_exists('token', $arguments) || is_null($arguments['token'])) {
            return null;
        }

        $response = $this->get('/archive_status', ['type' => 'timeline', 'sid' => $this->getSid()]);

        if (!is_array($response)) {
            return null;
        }

        foreach ($response as $value) {
            if (array_key_exists('token', $value) && $value['token'] == $arguments['token']) {
                $start = strtotime((string)$value['day_start']);

                $result = [];

                foreach ($value['timeline'] as $timeline) {
                    $result[] = [$start + $timeline['begin'], $start + $timeline['end']];
                }

                return $result;
            }
        }

        return null;
    }

    public function event(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): array
    {
        if (!array_key_exists('token', $arguments) || is_null($arguments['token'])) {
            return [];
        }

        $response = $this->get('/archive_events', ['token' => $arguments['token'], 'sid' => $this->getSid()]);

        if (!is_array($response)) {
            return [];
        }

        $timelineEvent = null;

        foreach ($response as $value) {
            if ($value['event_name'] === 'ActivityLevelEvent') {
                $timelineEvent = $value;

                break;
            }
        }

        if (!$timelineEvent) {
            return [];
        }

        $time = strtotime((string)$timelineEvent['day_start']);

        /** @var string $activities */
        $activities = $timelineEvent['activities'];
        $count = strlen($activities);

        $first = false;
        $result = [];
        $length = 0;

        for ($i = 0; $i < $count; ++$i) {
            if ($activities[$i] !== '0') {
                $stamp = $time + $i;

                if (!$first) {
                    $result[] = [$stamp, $stamp, -1];
                    ++$length;

                    $first = true;

                    continue;
                }

                if ($result[$length - 1][1] === $stamp - 1) {
                    $result[$length - 1][1] = $stamp;
                } else {
                    $result[] = [$stamp, $stamp, -1];

                    ++$length;
                }
            }
        }

        return $result;
    }

    public function command(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, DvrCommand $command, array $arguments): mixed
    {
        if (!array_key_exists('token', $arguments) || is_null($arguments['token'])) {
            return null;
        }

        if ($command === DvrCommand::PLAY && array_key_exists('seek', $arguments) && array_key_exists('from', $arguments) && array_key_exists('to', $arguments) && !is_null($arguments['to'])) {
            $response = $this->get('/archive_command', ['command' => 'play', 'direction' => 1, 'start' => $arguments['seek'] ?: $arguments['from'], 'stop' => $arguments['to'], 'speed' => $arguments['speed'] ?: 1, 'token' => $arguments['token'], 'sid' => $this->getSid()]);
            if (array_key_exists('success', $response) && $response['success'] == 1) {
                if (array_key_exists('first_frame_ts', $response)) {
                    return ['seek' => strtotime((string)$response['first_frame_ts'])];
                }

                return true;
            }

            return false;
        }

        if ($command === DvrCommand::PAUSE) {
            $response = $this->get('/archive_command', ['command' => 'stop', 'token' => $arguments['token'], 'sid' => $this->getSid()]);
            return array_key_exists('success', $response) && $response['success'] == 1;
        }

        if ($command === DvrCommand::SEEK && $arguments['seek']) {
            $response = $this->get('/archive_command', ['command' => 'seek', 'direction' => 1, 'timestamp' => $arguments['seek'], 'token' => $arguments['token'], 'sid' => $this->getSid()]);
            return array_key_exists('success', $response) && $response['success'] == 1;
        }

        if ($command === DvrCommand::SPEED && $arguments['speed'] && in_array($arguments['speed'], $this->capabilities()['speed'])) {
            return $this->command($identifier, $camera, $container, $stream, DvrCommand::PLAY, $arguments);
        }

        if ($command === DvrCommand::PING) {
            $setting = $this->getSetting();
            if ($setting === null || $setting === []) {
                return null;
            }

            $rtsp = array_key_exists('rtsp', $setting) ? $setting['rtsp'] : 554;
            $request = client_request('GET', (string)uri($this->server->url)->withScheme('http')->withPort($rtsp)->withPath($arguments['token'])->withQuery('ping'));
            $response = $this->client->send($request, $this->clientOption);
            return $response->getStatusCode() === 200;
        }

        if ($command === DvrCommand::STATUS) {
            $response = $this->get('/archive_status', ['type' => 'state', 'sid' => $this->getSid()]);

            if (!is_array($response)) {
                return null;
            }

            foreach ($response as $value) {
                if (array_key_exists('token', $value) && $value['token'] === $arguments['token']) {
                    return ['seek' => strtotime((string)$value['time']), 'speed' => intval($value['speed'])];
                }
            }
        }

        return null;
    }

    private function getRtspStream(DeviceCamera $camera, string $stream): ?array
    {
        $setting = $this->getSetting();

        if ($setting === null || $setting === []) {
            return null;
        }

        $rtsp = array_key_exists('rtsp', $setting) ? $setting['rtsp'] : 554;
        $response = $this->get('/get_video', ['channel' => $camera->dvr_stream, 'container' => DvrContainer::RTSP->value, 'stream' => $stream, 'sid' => $this->getSid()]);

        if (array_key_exists('success', $response) && $response['success']) {
            return [(string)uri($this->server->url)->withScheme('rtsp')->withPort($rtsp)->withPath($response['token']), $response['token']];
        }

        return null;
    }
}