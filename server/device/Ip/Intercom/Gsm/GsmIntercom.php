<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Gsm;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Setting\Gms\GmsInterface;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Service\SmsService;

class GmsIntercom extends IntercomDevice implements GmsInterface
{
    /**
     * @inheritDoc
     */
    public function addPhone(string $phone): void
    {
        $gms = $this->resolver->int(ConfigKey::Gsm, 1);
        $template = $this->resolver->string(ConfigKey::GsmAdd->with(['gms' => $gms]));
        $message = $this::template($template, ['password' => $this->password, 'phone' => $phone]);

        if (!container(SmsService::class)->send($this->login, $message)) {
            throw new DeviceException($this, 'Не удалось отправить СМС');
        }
    }

    /**
     * @inheritDoc
     */
    public function removePhone(string $phone): void
    {
        $gms = $this->resolver->int(ConfigKey::Gsm, 1);
        $template = $this->resolver->string(ConfigKey::GsmRemove->with(['gms' => $gms]));
        $message = $this::template($template, ['password' => $this->password, 'phone' => $phone]);

        if (!container(SmsService::class)->send($this->login, $message)) {
            throw new DeviceException($this, 'Не удалось отправить СМС');
        }
    }
}