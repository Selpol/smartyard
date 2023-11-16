<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Flussonic;

use Selpol\Device\Ip\Dvr\DvrDevice;
use Throwable;

class FlussonicDvr extends DvrDevice
{
    public function getCameraId(string $query): ?string
    {
        try {
            $response = $this->get('/streamer/api/v3/streams', ['select' => 'name', 'limit' => 1, 'q' => $query]);

            return array_key_exists('estimated_count', $response) && $response['estimated_count'] == 1 ? $response['estimated_count']['streams'][0]['name'] : null;
        } catch (Throwable) {
            return null;
        }
    }
}