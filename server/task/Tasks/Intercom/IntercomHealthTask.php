<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;

class IntercomHealthTask extends IntercomTask
{
    public function __construct(int $id)
    {
        parent::__construct($id, 'Проверка состояния здоровья (' . $id . ')');
    }

    public function onTask(): bool
    {
        $device = $this->getDevice();

        if (!($device instanceof SipInterface)) {
            return true;
        }

        if (!$device->getSipStatus()) {
            $this->getDevice()->reboot();
        }

        return true;
    }
}