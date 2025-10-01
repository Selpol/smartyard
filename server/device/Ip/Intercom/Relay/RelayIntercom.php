<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Relay;

use Selpol\Device\Ip\InfoDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\DDns;
use Selpol\Device\Ip\Intercom\Setting\Common\Mifare;
use Selpol\Device\Ip\Intercom\Setting\Common\Ntp;
use Selpol\Device\Ip\Intercom\Setting\Common\Relay;
use Selpol\Device\Ip\Intercom\Setting\Common\Room;
use Selpol\Device\Ip\Intercom\Setting\Common\Stun;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Feature\Config\ConfigKey;

class RelayIntercom extends IntercomDevice implements CommonInterface
{
    public string $login = 'user';

    public function getSysInfo(): InfoDevice
    {
        return new InfoDevice('RX', 'RX', '0.0.1', '0.0.1', null);
    }

    public function open(int $value): void
    {
        $map = explode(',', $this->resolver->string(ConfigKey::OutputMap, $value . ':' . $value));

        foreach ($map as $item) {
            if (str_starts_with($item, $value . ':')) {
                $this->post('/api/v1/open/' . substr($item, strlen((string) $value) + 1), ['invert' => $this->resolver->bool(ConfigKey::OutputInvert, false)]);

                return;
            }
        }

        $this->post('/api/v1/open/' . $value);
    }

    /**
     * @inheritDoc
     */
    public function getAutoCollectKey(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDDns(): DDns
    {
        return new DDns(false, '', 0);
    }

    /**
     * @inheritDoc
     */
    public function getGates(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getIndividualLevels(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMifare(): Mifare
    {
        return new Mifare(false, '', 0);
    }

    /**
     * @inheritDoc
     */
    public function getNtp(): Ntp
    {
        return new Ntp('', 0, '');
    }

    /**
     * @inheritDoc
     */
    public function getRelay(int $type): Relay
    {
        $response = $this->get('/api/v1/setting');

        return new Relay(true, $response['open_duration']);
    }

    /**
     * @inheritDoc
     */
    public function getRoom(): Room
    {
        return new Room('', '');
    }

    /**
     * @inheritDoc
     */
    public function getStun(): Stun
    {
        return new Stun('', 0);
    }

    /**
     * @inheritDoc
     */
    public function getSyslog(): Syslog
    {
        return new Syslog('', 0);
    }

    /**
     * @inheritDoc
     */
    public function getUPnP(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function setAutoCollectKey(bool $value): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setDDns(DDns $dDns): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setGates(array $value): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setGatesMode(int $value): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setIndividualLevels(bool $value): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setMifare(Mifare $mifare): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setNtp(Ntp $ntp): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setRelay(Relay $relay, int $type): bool
    {
        $this->post('/api/v1/setting', ['open_duration' => $relay->openDuration]);

        return $this->lastResponse?->getStatusCode() == 200;
    }

    /**
     * @inheritDoc
     */
    public function setRoom(Room $room): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setStun(Stun $stun): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setSyslog(Syslog $syslog): void
    {
    }

    /**
     * @inheritDoc
     */
    public function setUPnP(bool $value): void
    {
    }

    public function setServiceMode(bool $value): void
    {
    }
}
