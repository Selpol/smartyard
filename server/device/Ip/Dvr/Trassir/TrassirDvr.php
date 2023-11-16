<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Trassir;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

class TrassirDvr extends DvrDevice
{
    private ?string $sid = null;

    public function __construct(Uri $uri, string $login, #[SensitiveParameter] string $password, DvrModel $model)
    {
        parent::__construct($uri, $login, $password, $model);

        $this->clientOption->raw(CURLOPT_SSL_VERIFYHOST, 0)->raw(CURLOPT_SSL_VERIFYPEER, 0);
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
        if (is_null($this->sid)) {
            $response = $this->get('/login', ['username' => $this->login, 'password' => $this->password]);

            if (array_key_exists('sid', $response))
                $this->sid = $response['sid'];
            else throw new DeviceException($this, 'Не удалось авторизироваться');
        }

        return $this->sid;
    }
}