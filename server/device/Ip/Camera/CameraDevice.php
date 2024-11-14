<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
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

        $login = $this->resolver->string('auth.login');

        if ($login !== null) {
            $this->login = $login;
        }

        match ($this->resolver->string('auth', 'basic')) {
            "any_safe" => $this->clientOption->anySafe($this->login, $password),
            "basic" => $this->clientOption->basic($this->login, $password),
            "digest" => $this->clientOption->digest($this->login, $password),
        };

        if (!$this->debug) {
            $this->debug = $this->resolver->bool('debug', false);
        }

        $this->setLogger(file_logger('camera'));
    }

    public function getScreenshot(): Stream
    {
        throw new DeviceException($this, 'Не удалось получить скриншот');
    }
}