<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\DeviceLogger;
use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    public readonly ConfigResolver $resolver;

    public readonly bool $mifare;

    public readonly ?string $mifareKey;

    public readonly ?int $mifareSector;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, public IntercomModel $model, public DeviceIntercom $intercom, ConfigResolver $resolver)
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

        $this->timeout = $this->resolver->int(ConfigKey::Timeout, 0);
        $this->prepare = $this->resolver->int(ConfigKey::Prepare, 1);

        if ($this->resolver->bool(ConfigKey::Mifare, false) === true) {
            $key = $this->resolver->string(ConfigKey::MifareKey);
            $sector = $this->resolver->string(ConfigKey::MifareSector);

            if ($key && str_starts_with($key, 'ENV_')) {
                $key = env(substr($key, 4));
            }

            if ($sector && str_starts_with($sector, 'ENV_')) {
                $sector = env(substr($sector, 4));
            }

            $this->mifare = $key && $sector;

            $this->mifareKey = $key;
            $this->mifareSector = intval($sector);
        } else {
            $this->mifare = false;

            $this->mifareKey = null;
            $this->mifareSector = null;
        }

        $log = self::template($this->resolver->string(ConfigKey::Log, 'intercom'), ['model' => strtolower($this->model->vendor), 'date' => date('Y-m-d'), 'id' => (string)$this->intercom->house_domophone_id]);
        $dir = dirname(path('var/log/' . $log));

        if (!is_dir($dir)) {
            mkdir($dir, recursive: true);
        }

        $this->setLogger(new DeviceLogger(path('var/log/' . $log . '.log')));
    }

    public function specification(): string
    {
        throw new DeviceException($this, 'Метод спецификации устройства не реализован');
    }

    public function open(int $value): void {}

    public function call(int $apartment): void {}

    public function callStop(): void {}

    public function reboot(): void {}

    public function reset(): void {}
}
