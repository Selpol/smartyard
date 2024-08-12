<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

use Psr\Http\Message\StreamInterface;
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

            return array_key_exists('streams', $response) ? array_map(static fn(array $stream) => ['id' => $stream['name'], 'title' => $stream['title'] ?? $stream['name']], $response['streams']) : [];
        } catch (Throwable) {
            return [];
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
        $device = container(DeviceService::class)->cameraByEntity($camera);

        if (!$device) {
            return null;
        }

        if ($device->model->vendor === 'FAKE' && $camera->screenshot) {
            return $this->client->send(client_request('GET', $camera->screenshot))->getBody();
        }

        return $device->getScreenshot();
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        if ($arguments['time']) {
            return $this->getUrl($camera) . '/' . $arguments['time'] . '-preview.mp4?token=' . $identifier->value;
        }

        if ($camera->model === 'fake') {
            return $this->getUrl($camera) . '/preview.mp4?token=' . $identifier->value;
        }

        return config_get('api.mobile') . '/dvr/screenshot/' . $identifier->value;
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): ?DvrOutput
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP) {
                return new DvrOutput($container, uri($this->getUrl($camera))->withScheme('rtsp')->withQuery('token=' . $identifier->value));
            } else if ($container === DvrContainer::HLS) {
                return new DvrOutput($container, $this->getUrl($camera) . '/index.m3u8?token=' . $identifier->value);
            } else if ($container === DvrContainer::RTC) {
                $stream = new Stream(container(StreamerFeature::class)->random());

                $stream->source((string)uri($this->getUrl($camera))->withScheme('rtsp')->withQuery('token=' . $identifier->value))->input(StreamInput::RTSP)->output(StreamOutput::RTC);

                container(StreamerFeature::class)->stream($stream);

                return new DvrOutput(
                    $container,
                    new DvrStreamer($stream->getServer()->url, $stream->getServer()->id . '-' . $stream->getToken(), $stream->getOutput())
                );
            }
        } else if ($stream === DvrStream::ARCHIVE) {
            $timeline = $this->timeline($identifier, $camera, ['short' => true]);

            if (!$timeline) {
                return null;
            }

            $from = $timeline[0][0];
            $to = $timeline[0][1];

            $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);

            return new DvrOutput(
                DvrContainer::HLS,
                new DvrArchive($this->getUrl($camera) . '/archive-' . $seek . '-' . ($to - $seek) . '.m3u8?token=' . $identifier->value . '&event=true', $from, $to, $seek, $camera->timezone, null)
            );
        }

        return null;
    }

    public function timeline(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?array
    {
        if (!array_key_exists('short', $arguments)) {
            $from = time() - 86400 * 60;
            $to = time() + 60;

            $response = $this->get($camera->dvr_stream . '/recording_status.json?token=' . $identifier->value . '&from=' . $from . '&to=' . $to . '&request=ranges');

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

            return array_map(static fn(array $value) => [$value['from'], $value['from'] + $value['duration']], $ranges);
        }

        /** @var array<string, array<string, int>> $timeline */
        $timeline = $this->get($camera->dvr_stream . '/recording_status.json?token=' . $identifier->value);

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