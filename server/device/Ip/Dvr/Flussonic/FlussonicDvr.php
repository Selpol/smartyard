<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

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

    public function updateCamera(DeviceCamera $camera): bool
    {
        try {
            $stream = $this->get('/streamer/api/v3/streams/' . $camera->dvr_stream);

            if (!array_key_exists('inputs', $stream) || count($stream['inputs']) == 0)
                return false;

            $inputs = array_map(static function (array $input) use ($camera) {
                $url = uri($input['url']);

                $url->withUserInfo(explode(':', $url->getUserInfo())[0], $camera->credentials);

                return ['url' => (string)$url];
            }, $stream['inputs']);

            $response = $this->put('/streamer/api/v3/streams/' . $camera->dvr_stream, ['inputs' => $inputs]);

            return !array_key_exists('errors', $response);
        } catch (Throwable) {
            return false;
        }
    }
}