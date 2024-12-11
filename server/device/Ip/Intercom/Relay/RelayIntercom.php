<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Relay;

use Selpol\Device\Ip\InfoDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;

class RelayIntercom extends IntercomDevice
{
    public string $login = 'user';

    public function getSysInfo(): InfoDevice
    {
        return new InfoDevice('RX', 'RX', '0.0.1', '0.0.1', null);
    }

    public function open(int $value): void
    {
        $map = explode(',', $this->resolver->string('output.map', $value . ':' . $value));

        foreach ($map as $item) {
            if (str_starts_with($item, $value . ':')) {
                $this->post('/api/v1/open/' . substr($item, strlen((string)$value) + 1));

                return;
            }
        }

        $this->post('/api/v1/open/' . $value);
    }
}
