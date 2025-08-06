<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

use Psr\Http\Message\StreamInterface;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Dvr\Common\DvrArchive;
use Selpol\Device\Ip\Dvr\Common\DvrCommand;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrStreamer;
use Selpol\Device\Ip\Dvr\Common\DvrOutput;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Streamer\Stream;
use Selpol\Feature\Streamer\StreamerFeature;
use Selpol\Feature\Streamer\StreamInput;
use Selpol\Feature\Streamer\StreamOutput;
use Selpol\Service\DeviceService;
use Throwable;

class FlussonicDvr extends DvrDevice
{
    public function getCameras(): array
    {
        try {
            $response = $this->get('/streamer/api/v3/streams', ['select' => 'name,title', 'limit' => 10000]);

            return array_key_exists('streams', $response) ? array_map(static fn(array $stream): array => ['id' => $stream['name'], 'title' => $stream['title'] ?? $stream['name']], $response['streams']) : [];
        } catch (Throwable) {
            return [];
        }
    }

    public function getCamera(string $id): ?array
    {
        try {
            $response = $this->get('/streamer/api/v3/streams/' . $id);

            if (!array_key_exists('inputs', $response)) {
                return null;
            }

            $inputs = array_filter($response['inputs'], static fn(array $value) => $value['priority'] == 1);

            if (count($inputs) == 0) {
                return null;
            }

            return [
                'title' => $response['title'],
                'url' => $inputs[0]['url'],
                'ip' => $inputs[0]['stats']['ip']
            ];
        } catch (Throwable) {
            return null;
        }
    }

    public function getCameraId(string $query): ?string
    {
        try {
            $response = $this->get('/streamer/api/v3/streams', ['select' => 'name', 'limit' => 1, 'q' => $query]);

            return array_key_exists('streams', $response) && count($response['streams']) > 0 ? $response['streams'][0]['name'] : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function capabilities(): array
    {
        return [
            'poster' => true,
            'preview' => true,

            'online' => true,
            'archive' => true,

            'command' => [DvrCommand::SEEK->value],
            'speed' => [1, 2, 4]
        ];
    }

    public function updateCamera(DeviceCamera $camera): void
    {
        $stream = $this->get('/streamer/api/v3/streams/' . $camera->dvr_stream);

        if (!array_key_exists('inputs', $stream) && !is_array($stream['inputs'])) {
            throw new DeviceException($this, 'Не удалось получить поток');
        }

        $inputs = $stream['inputs'];

        $update = false;

        for ($i = 0; $i < count($inputs); $i++) {
            $uri = uri($inputs[$i]['url']);

            list($user, $password) = explode(':', $uri->getUserInfo());

            if ($password !== $camera->credentials) {
                $uri->withUserInfo($user, $camera->credentials);

                $inputs[$i]['url'] = (string)$uri;

                $update = true;
            }
        }

        if ($update) {
            $stream = $this->put('/streamer/api/v3/streams/' . $camera->dvr_stream, ['inputs' => $inputs]);

            if (!array_key_exists('inputs', $stream) && !is_array($stream['inputs'])) {
                throw new DeviceException($this, 'Не удалось обновить поток');
            }
        }
    }

    public function identifier(DeviceCamera $camera, int $time, ?int $subscriberId): ?DvrIdentifier
    {
        $start = $time - 300;
        $end = $time + 3600;

        return new DvrIdentifier($camera->camera_id, $camera->dvr_server_id, $start, $end, $subscriberId);
    }

    public function screenshot(DvrIdentifier $identifier, DeviceCamera $camera, ?int $time): ?StreamInterface
    {
        if ($time) {
            $timeline = $this->timeline($identifier, $camera, ['short' => true]);

            if ($timeline === null || $timeline === []) {
                return null;
            }

            $from = $timeline[0][0];
            $to = $timeline[0][1];
            $time = max(min($to, $time), $from);

            $filename = "/tmp/" . uniqid('preview_') . ".jpeg";
            $url = $this->getUrl($camera) . '/' . $time . '-preview.mp4';

            shell_exec('ffmpeg -y -i ' . $url . ' -vframes 1 ' . $filename);

            file_logger('dvr')->debug('url', [$url]);

            if (file_exists($filename)) {
                $contents = stream(fopen($filename, 'rb'))->getContents();

                unlink($filename);

                return stream($contents);
            }
        }

        $device = container(DeviceService::class)->cameraByEntity($camera);

        if (!$device) {
            return null;
        }

        return $device->getScreenshot();
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        if ($arguments['time']) {
            return $this->getUrl($camera) . '/' . $arguments['time'] . '-preview.mp4?token=' . $this->getToken($camera, $identifier->start, $identifier->end);
        }

        if ($camera->model === 'fake') {
            return $this->getUrl($camera) . '/preview.mp4?token=' . $this->getToken($camera, $identifier->start, $identifier->end);
        }

        return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->toToken();
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): ?DvrOutput
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP) {
                return new DvrOutput($container, uri($this->getUrl($camera))->withScheme('rtsp')->withQuery('token=' . $this->getToken($camera, $identifier->start, $identifier->end))->__toString());
            }

            if ($container === DvrContainer::HLS) {
                return new DvrOutput($container, $this->getUrl($camera) . '/index.m3u8?token=' . $this->getToken($camera, $identifier->start, $identifier->end));
            }

            if ($container === DvrContainer::STREAMER_RTC || $container === DvrContainer::STREAMER_RTSP) {
                $server = container(StreamerFeature::class)->random();

                $stream = new Stream($server, $server->id . '-' . uniqid(more_entropy: true));
                $stream->source((string)uri($this->getUrl($camera))->withScheme('rtsp')->withQuery('token=' . $this->getToken($camera, $identifier->start, $identifier->end)))->input(StreamInput::RTSP)->output($container == DvrContainer::STREAMER_RTC ? StreamOutput::RTC : StreamOutput::RTSP);

                container(StreamerFeature::class)->stream($stream);

                return new DvrOutput(
                    $container,
                    new DvrStreamer($stream->getServer()->url, $stream->getToken(), $stream->getOutput())
                );
            }
        } elseif ($stream === DvrStream::ARCHIVE) {
            $timeline = $this->timeline($identifier, $camera, ['short' => true]);

            if ($timeline === null || $timeline === []) {
                return null;
            }

            $from = $timeline[0][0];
            $to = $timeline[0][1];
            $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);

            return new DvrOutput(
                DvrContainer::HLS,
                new DvrArchive($this->getUrl($camera) . '/archive-' . $seek . '-' . ($to - $seek) . '.m3u8?token=' . $this->getToken($camera, $identifier->start, $identifier->end) . '&event=true', $from, $to, $seek, $camera->timezone, null)
            );
        }

        return null;
    }

    public function segment(DvrIdentifier $identifier, DeviceCamera $camera, int $start, int $end): ?DvrOutput
    {
        $timeline = $this->timeline($identifier, $camera, ['short' => true]);

        if ($timeline === null || $timeline === []) {
            return null;
        }

        $from = $timeline[0][0];
        $to = $timeline[0][1];

        if ($from > $start || $to < $end) {
            return null;
        }

        return new DvrOutput(
            DvrContainer::HLS,
            new DvrArchive($this->getUrl($camera) . '/archive-' . $from . '-' . ($to - $from) . '.m3u8?token=' . $this->getToken($camera, $identifier->start, $identifier->end) . '&event=true', $from, $to, $from, $camera->timezone, null)
        );
    }

    public function timeline(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?array
    {
        if (!array_key_exists('short', $arguments)) {
            $from = time() - 86400 * 60;
            $to = time() + 60;

            $response = $this->get($camera->dvr_stream . '/recording_status.json?token=' . $this->getToken($camera, $identifier->start, $identifier->end) . '&from=' . $from . '&to=' . $to . '&request=ranges');

            if (!$response || !is_array($response)) {
                return null;
            }

            /** @var array|null $ranges */
            $ranges = null;

            foreach ($response as $value) {
                if ($value['stream'] == $camera->dvr_stream) {
                    if (array_key_exists('ranges', $value)) {
                        $ranges = $value['ranges'];
                    }

                    break;
                }
            }

            if (!$ranges) {
                return null;
            }

            return array_map(static fn(array $value): array => [$value['from'], $value['from'] + $value['duration']], $ranges);
        }

        /** @var array<string, array<string, int>> $timeline */
        $timeline = $this->get($camera->dvr_stream . '/recording_status.json?token=' . $this->getToken($camera, $identifier->start, $identifier->end));

        if (!$timeline || !array_key_exists($camera->dvr_stream, $timeline)) {
            return null;
        }

        return [[$timeline[$camera->dvr_stream]['from'], $timeline[$camera->dvr_stream]['to']]];
    }

    public function command(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, DvrCommand $command, array $arguments): mixed
    {
        if ($command === DvrCommand::SEEK && $arguments['seek']) {
            return ['archive' => $this->video($identifier, $camera, $container, $stream, ['time' => $arguments['seek']])];
        }

        return null;
    }

    private function getToken(DeviceCamera $camera, int $start, int $end): string
    {
        $salt = bin2hex(openssl_random_pseudo_bytes(16));
        $hash = sha1($camera->dvr_stream . 'no_check_ip' . $start . $end . $this->server->token . $salt);

        return $hash . '-' . $salt . '-' . $end . '-' . $start;
    }

    private function getUrl(DeviceCamera $camera): string
    {
        return $this->server->url . '/' . $camera->dvr_stream;
    }
}
