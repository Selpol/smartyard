<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

use Selpol\Device\Ip\Dvr\Common\DvrArchive;
use Selpol\Device\Ip\Dvr\Common\DvrContainer;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Selpol\Device\Ip\Dvr\Common\DvrStream;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
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

    public function identifier(DeviceCamera $camera, int $time, ?int $subscriberId): ?DvrIdentifier
    {
        $start = $time - 3600 * 192;
        $end = $time + 3600 * 3;

        return new DvrIdentifier($this->getToken($camera, $start, $end), $start, $end, $subscriberId);
    }

    public function preview(DvrIdentifier $identifier, DeviceCamera $camera, array $arguments): ?string
    {
        if ($arguments['time'])
            return $this->getUrl($camera) . '/' . $arguments['time'] . '-preview.mp4?token=' . $identifier->value;

        return $this->getUrl($camera) . '/preview.jpg?token=' . $identifier->value;
    }

    public function video(DvrIdentifier $identifier, DeviceCamera $camera, DvrContainer $container, DvrStream $stream, array $arguments): DvrArchive|string|null
    {
        if ($stream === DvrStream::ONLINE) {
            if ($container === DvrContainer::RTSP)
                return uri($this->getUrl($camera))->withScheme('rtsp')->withQuery('token=' . $identifier->value);
            else if ($container === DvrContainer::HLS)
                return $this->getUrl($camera) . '/index.m3u8?token=' . $identifier->value;
        } else if ($stream === DvrStream::ARCHIVE) {
            if ($container == DvrContainer::HLS) {
                /** @var array<string, array<string, int>> $timeline */
                $timeline = $this->get($camera->dvr_stream . '/recording_status.json?token=' . $identifier->value);

                if (!$timeline || !array_key_exists($camera->dvr_stream, $timeline))
                    return null;

                $from = $timeline[$camera->dvr_stream]['from'];
                $to = $timeline[$camera->dvr_stream]['to'];

                $seek = min(max($from, $arguments['time'] ?? ($to - 180)), $to);

                return new DvrArchive($this->getUrl($camera) . '/archive-' . $seek . '-' . ($to - $seek) . '.m3u8?token=' . $identifier->value, $from, $to, $seek);
            }
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