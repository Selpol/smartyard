<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

use Selpol\Device\Ip\Dvr\DvrDevice;
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
}