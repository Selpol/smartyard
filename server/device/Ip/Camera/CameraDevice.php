<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Ip\DeviceLogger;
use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Http\Stream;
use Selpol\Framework\Http\Uri;

abstract class CameraDevice extends IpDevice
{
    public readonly ConfigResolver $resolver;

    public function __construct(Uri $uri, string $password, public CameraModel $model, public DeviceCamera $camera, ConfigResolver $resolver)
    {
        parent::__construct($uri, $password);

        $this->resolver = $resolver;

        $login = $this->resolver->string(ConfigKey::AuthLogin);

        if ($login !== null) {
            $this->login = $login;
        }

        match ($this->resolver->string(ConfigKey::Auth, 'basic')) {
            "any_safe" => $this->clientOption->anySafe($this->login, $password),
            "basic" => $this->clientOption->basic($this->login, $password),
            "digest" => $this->clientOption->digest($this->login, $password),
        };

        if (!$this->debug) {
            $this->debug = $this->resolver->bool(ConfigKey::Debug, false);
        }

        $log = self::template($this->resolver->string(ConfigKey::Log, 'camera'), ['model' => strtolower($this->model->vendor), 'date' => date('Y-m-d'), 'id' => (string)$this->camera->camera_id]);
        $dir = dirname(path('var/log/' . $log));

        if (!is_dir($dir)) {
            mkdir($dir, recursive: true);
        }

        $this->setLogger(new DeviceLogger(path('var/log/' . $log . '.log')));
    }

    public function getScreenshot(): Stream
    {
        $screenshot = $this->resolver->string(ConfigKey::Screenshot);

        if ($screenshot) {
            $screenshot = self::template($screenshot, ['url' => $this->camera->url, 'ip' => $this->camera->ip]);

            return $this->client->send(client_request('GET', $screenshot), $this->clientOption)->getBody();
        }

        return $this->getScreenshotInternal();
    }

    protected abstract function getScreenshotInternal(): Stream;
}