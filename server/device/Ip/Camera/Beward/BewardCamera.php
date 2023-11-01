<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Beward;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Framework\Http\Stream;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

class BewardCamera extends CameraDevice
{
    use BewardTrait;

    public string $login = 'admin';

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, CameraModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->digest($this->login, $this->password);
    }

    public function getScreenshot(): Stream
    {
        try {
            return $this->client->send(request('GET', $this->uri . '/cgi-bin/images_cgi?channel=0'), $this->clientOption)->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}