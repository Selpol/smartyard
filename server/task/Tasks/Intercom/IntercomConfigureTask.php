<?php declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\Gate;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyHandlerInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Service\DatabaseService;
use Selpol\Service\DeviceService;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Task\Trait\TaskUniqueTrait;

class IntercomConfigureTask extends IntercomTask implements TaskUniqueInterface
{
    use TaskUniqueTrait;

    public int $id;

    public function __construct(int $id)
    {
        parent::__construct($id, 'Настройка домофона (' . $id . ')');
    }

    public function onTask(): bool
    {
        $households = container(HouseFeature::class);

        $deviceIntercom = DeviceIntercom::findById($this->id, setting: setting()->nonNullable());
        $deviceModel = IntercomModel::model($deviceIntercom->model);

        if (!$deviceIntercom || !$deviceModel)
            throw new KernelException('Устройство не существует');

        $this->setProgress(1);

        $entrances = HouseEntrance::fetchAll(criteria()->equal('house_domophone_id', $this->id)->equal('domophone_output', 0));

        if (count($entrances) === 0)
            throw new KernelException('Устройство не привязанно к какому-то либо входу');

        $this->setProgress(2);

        $device = container(DeviceService::class)->intercomByEntity($deviceIntercom);

        if (!$device)
            throw new KernelException('Не удалось получить устройство');

        if (!$device->ping())
            throw new DeviceException($device, 'Устройство не доступно');

        if ($deviceIntercom->first_time == 0) {
            $deviceIntercom->first_time = 1;

            $deviceIntercom->update();
            $deviceIntercom->refresh();
        }

        $this->setProgress(5);

        $clean = $device->getIntercomClean();
        $ntp = $device->getIntercomNtp();

        if ($device instanceof AudioInterface)
            $this->audio($device);

        $this->setProgress(5);

        if ($device instanceof VideoInterface)
            $this->video($device, $entrances);

        $this->setProgress(10);

        if ($device instanceof SipInterface)
            $this->sip($device, $deviceIntercom, $clean);

        $this->setProgress(20);

        if ($device instanceof CommonInterface)
            $this->common($device, $entrances, $clean, $ntp);

        $this->setProgress(30);

        if ($device instanceof CmsInterface)
            $this->cms($device, $entrances);

        $this->setProgress(50);

        if ($device instanceof ApartmentInterface) {
            /** @var array<int, HouseFlat> $flats */
            $flats = [];

            $this->apartment($device, $entrances, $flats);

            $this->setProgress(80);

            if ($device instanceof KeyInterface) {
                if ($device instanceof KeyHandlerInterface)
                    $device->handleKey($flats);
                else
                    $this->key($device, $flats);
            }

            $this->setProgress(90);

            if ($device instanceof CodeInterface)
                $this->code($device, $flats);

            $this->setProgress(95);

            if ($device instanceof CommonInterface) {
                if ($entrances[0]->shared)
                    $this->commonGates($device, $entrances[0], $flats);
            }
        }

        if ($device instanceof CommonInterface)
            $this->commonSyslog($device, $deviceModel);

        return true;
    }

    private function audio(IntercomDevice & AudioInterface $device): void
    {
        $defaultAudioLevels = $device->getDefaultAudioLevels();

        if (count($defaultAudioLevels->value) === 0)
            return;

        if (!$defaultAudioLevels->equal($device->getAudioLevels()))
            $device->setAudioLevels($defaultAudioLevels);
    }

    /**
     * @param VideoInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @return void
     */
    private function video(IntercomDevice & VideoInterface $device, array $entrances): void
    {
        $videoEncoding = $device->getVideoEncoding();

        $newVideoEncoding = clone $videoEncoding;
        $newVideoEncoding->primaryBitrate = 1024;
        $newVideoEncoding->secondaryBitrate = 512;

        if (!$newVideoEncoding->equal($videoEncoding))
            $device->setVideoEncoding($newVideoEncoding);

        $videoDetection = $device->getVideoDetection();

        $newVideoDetection = clone $videoDetection;
        $newVideoDetection->enable = true;

        if (!$newVideoDetection->equal($videoDetection))
            $device->setVideoDetection($newVideoDetection);

        $videoOverlay = $device->getVideoOverlay();

        $newVideoOverlay = clone $videoOverlay;
        $newVideoOverlay->title = $entrances[0]->caller_id;

        if (!$newVideoOverlay->equal($videoOverlay))
            $device->setVideoOverlay($newVideoOverlay);
    }

    private function sip(IntercomDevice & SipInterface $device, DeviceIntercom $deviceIntercom, array $clean): void
    {
        $server = container(SipFeature::class)->server('ip', $deviceIntercom->server)[0];

        $sip = $device->getSip();

        $newSip = clone $sip;
        $newSip->login = sprintf("1%05d", $deviceIntercom->house_domophone_id);
        $newSip->password = $device->password;
        $newSip->server = $server->internal_ip;
        $newSip->port = $server->internal_port;

        if (!$newSip->equal($sip))
            $device->setSip($newSip);

        $sipOption = $device->getSipOption();

        $newSipOption = clone $sipOption;
        $newSipOption->callTimeout = $clean['callTimeout'];
        $newSipOption->talkTimeout = $clean['talkTimeout'];
        $newSipOption->dtmf = [$deviceIntercom->dtmf, '2'];
        $newSipOption->echo = false;

        if (!$newSipOption->equal($sipOption))
            $device->setSipOption($newSipOption);
    }

    /**
     * @param CommonInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @param array $clean
     * @param array $ntpServer
     * @return void
     */
    public function common(IntercomDevice & CommonInterface $device, array $entrances, array $clean, array $ntpServer): void
    {
        $ntp = $device->getNtp();

        $newNtp = clone $ntp;
        $newNtp->server = $ntpServer[0];
        $newNtp->port = $ntpServer[1];
        $newNtp->timezone = config('timezone', 'Europe/Moscow');

        if (!$newNtp->equal($ntp))
            $device->setNtp($newNtp);

        $key = env('MIFARE_KEY');
        $sector = env('MIFARE_SECTOR');

        if ($key && $sector) {
            $mifare = $device->getMifare();

            $newMifare = clone $mifare;
            $newMifare->enable = true;
            $newMifare->key = $key;
            $newMifare->sector = intval($sector);

            if (!$newMifare->equal($mifare))
                $device->setMifare($mifare);
        }

        $room = $device->getRoom();

        $newRoom = clone $room;
        $newRoom->concierge = $clean['concierge'];
        $newRoom->sos = $clean['sos'];

        if (!$newRoom->equal($room))
            $device->setRoom($room);

        $relay = $device->getRelay();

        $newRelay = clone $relay;
        $newRelay->lock = !$entrances[0]->locks_disabled;
        $newRelay->openDuration = $clean['unlockTime'];

        if (!$newRelay->equal($relay))
            $device->setRelay($newRelay);

        $dDns = $device->getDDns();

        $newDDns = clone $dDns;
        $newDDns->enable = false;

        if (!$newDDns->equal($dDns))
            $device->setDDns($newDDns);

        if ($device->getUPnP())
            $device->setUPnP(false);

        if ($device->getAutoCollectKey())
            $device->setAutoCollectKey(false);
    }

    /**
     * @param CmsInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @return void
     */
    public function cms(IntercomDevice & CmsInterface $device, array $entrances): void
    {
        if ($entrances[0]->shared)
            return;

        if ($device->getCmsModel() != $entrances[0]->cms)
            $device->setCmsModel($entrances[0]->cms);

        $cms = container(HouseFeature::class)->getCms($entrances[0]->house_entrance_id);

        foreach ($cms as $value)
            $device->setCmsApartmentDeffer(new CmsApartment($value['cms'] + 1, $value['dozen'], $value['unit'], $value['apartment']));

        $device->defferCms();
    }

    /**
     * @param ApartmentInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @param array<int, HouseFlat> $flats
     * @return void
     */
    public function apartment(IntercomDevice & ApartmentInterface $device, array $entrances, array &$flats): void
    {
        $progress = 50;
        $delta = (80 - 50) / count($entrances);

        /** @var array<int, Apartment> $apartments */
        $apartments = array_reduce($device->getApartments(), static function (array $previous, Apartment $current) {
            $previous[$current->apartment] = $current;

            return $previous;
        }, []);

        /** @var array<int, bool> $processed */
        $processed = [];

        foreach ($entrances as $entrance) {
            $houseFlats = $entrance->flats;

            if (count($houseFlats) === 0)
                continue;

            $entranceLevels = array_filter(
                array_map(static fn(string $value) => intval($value), preg_split(',', $entrance->cms_levels)),
                static fn(int $value) => $value
            );

            foreach ($houseFlats as $flat) {
                if (!array_key_exists($flat->flat, $flats))
                    $flats[$flat->flat] = $flat;

                $flatEntrance = container(DatabaseService::class)->get('SELECT apartment, cms_levels FROM houses_entrances_flats WHERE house_entrance_id = :entrance_id AND house_flat_id = :flat_id', ['entrance_id' => $entrance->house_entrance_id, 'flat_id' => $flat->house_flat_id]);

                if (!$flatEntrance)
                    throw new DeviceException($device, 'Вход к квартире не привязан');

                $levels = array_filter(
                    array_map(static fn(string $value) => intval($value), preg_split(',', $flatEntrance['cms_levels'])),
                    static fn(int $value) => $value
                );

                $block = container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]) != null;

                $apartment = new Apartment(
                    $flat->flat,
                    $entrance->shared ? false : ($block ? false : $flat->cms_enabled),
                    $entrance->shared ? false : ($block ? false : $flat->sip_enabled),
                    array_key_exists(0, $levels) ? $levels[0] : (array_key_exists(0, $entranceLevels) ? $entranceLevels[0] : null),
                    array_key_exists(1, $levels) ? $levels[1] : (array_key_exists(1, $entranceLevels) ? $entranceLevels[1] : null),
                    $entrance->shared || $block ? [] : [sprintf('1%09d', $flat->house_flat_id)]
                );

                if (array_key_exists($flat->flat, $apartments)) {
                    if (!$apartment->equal($apartments[$flat->flat]))
                        $device->setApartment($apartment);
                } else {
                    $device->addApartment($apartment);

                    $apartments[$flat->flat] = $apartment;
                }

                $processed[$flat->flat] = true;
            }

            $progress += $delta;
            $this->setProgress($progress);
        }

        $removed = array_filter(array_keys($apartments), static fn(int $value) => !array_key_exists($value, $processed));

        foreach ($removed as $value) {
            $device->removeApartment($apartments[$value]);

            unset($apartments[$value]);
        }
    }

    /**
     * @param KeyInterface&IntercomDevice $device
     * @param array<int, HouseFlat> $flats
     * @return void
     */
    public function key(IntercomDevice & KeyInterface $device, array $flats): void
    {
        $progress = 80;
        $delta = (90 - 80) / count($flats);

        foreach ($flats as $apartment => $flat) {
            $flatKeys = HouseKey::fetchAll(criteria()->equal('access_to', $flat['flatId'])->equal('access_type', 2));

            /** @var array<string, Key> $keys */
            $keys = array_reduce($device->getKeys($apartment), static function (array $previous, Key $current) {
                $previous[$current->key] = $current;

                return $previous;
            }, []);

            foreach ($flatKeys as $index => $flatKey) {
                if (array_key_exists($flatKey->rfid, $keys)) {
                    unset($keys[$flatKey->rfid]);
                    unset($flatKeys[$index]);

                    continue;
                }

                $device->addKey(new Key($flatKey->rfid, $apartment));
            }

            foreach ($keys as $key)
                $device->removeKey($key);

            $progress += $delta;
            $this->setProgress($progress);
        }
    }

    /**
     * @param CodeInterface&IntercomDevice $device
     * @param array<int, HouseFlat> $flats
     * @return void
     */
    public function code(IntercomDevice & CodeInterface $device, array $flats): void
    {
        $progress = 90;
        $delta = (95 - 90) / count($flats);

        foreach ($flats as $apartment => $flat) {
            $code = intval($flat->open_code) ?: 0;
            $codes = $device->getCodes($apartment);

            if ($code) {
                if (count($codes) === 0)
                    $device->addCode(new Code($code, $apartment));
                else if (count($codes) === 1) {
                    if (!$codes[0]->code != $code) {
                        $device->removeCode($codes[0]);
                        $device->addCode(new Code($code, $apartment));
                    }
                } else {
                    foreach ($codes as $code)
                        $device->removeCode($code);

                    $device->addCode(new Code($code, $apartment));
                }
            } else {
                foreach ($codes as $code)
                    $device->removeCode($code);
            }

            $progress += $delta;
            $this->setProgress($progress);
        }
    }

    /**
     * @param CommonInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @param array<int, HouseFlat> $flats
     * @return void
     */
    public function commonGates(IntercomDevice & CommonInterface $device, HouseEntrance $entrance, array $flats): void
    {
        usort($flats, static fn(HouseFlat $a, HouseFlat $b) => $a->flat <=> $b->flat);

        /**
         * address_house_id > prefix
         * @var array<int, int> $prefixes
         */
        $prefixes = [];

        /**
         * house_entrance_id -> HouseEntrance
         * @var array<int, HouseEntrance> $entrances
         */
        $entrances = [];

        /**
         * house_domophone_id -> DeviceIntercom
         * @var array<int, DeviceIntercom> $intercoms
         */
        $intercoms = [];

        /** @var Gate[] $gates */
        $gates = [];

        $pivotCriteria = criteria()->in('house_flat_id', array_map(static fn(HouseFlat $flat) => $flat->house_flat_id, $flats));
        $pivot = container(DatabaseService::class)->get('SELECT house_entrance_id, house_flat_id FROM houses_entrances_flats' . $pivotCriteria->getSqlString(), $pivotCriteria->getSqlParams());

        $pivot = array_reduce($pivot, static function (array $previous, array $current) {
            if (!array_key_exists($current['house_flat_id'], $previous))
                $previous[$current['house_flat_id']] = [];

            $previous[$current['house_flat_id']][] = $current['house_entrance_id'];

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            if (!array_key_exists($flat->house_flat_id, $pivot))
                continue;

            if (!array_key_exists($flat->address_house_id, $prefixes))
                $prefixes[$flat->address_house_id] = container(DatabaseService::class)->get('SELECT prefix FROM houses_houses_entrances WHERE address_house_id = :house_id AND house_entrance_id = :entrance_id', ['house_id' => $flat->address_house_id, 'entrance_id' => $entrance->house_entrance_id], options: ['singlify'])['prefix'];

            /** @var HouseEntrance|null $flatEntrance */
            $flatEntrance = null;

            foreach ($pivot[$flat->house_flat_id] as $entranceId) {
                if (array_key_exists($entranceId, $entrances)) {
                    $flatEntrance = $entrances[$entranceId];
                    break;
                }

                $temp = HouseEntrance::findById($entranceId, criteria()->equal('shared', 0));

                if (!$temp)
                    continue;

                $entrances[$entranceId] = $temp;
                $flatEntrance = $temp;

                break;
            }

            if (!$flatEntrance)
                continue;

            if (!array_key_exists($flatEntrance->house_domophone_id, $intercoms))
                $intercoms[$entrance->house_domophone_id] = $entrance->intercom;

            if (count($gates) > 0 && $gates[count($gates) - 1]->end + 1 == $flat->flat)
                $gates[count($gates) - 1]->end++;
            else $gates[] = new Gate(
                $intercoms[$flatEntrance->house_domophone_id]->ip,
                $prefixes[$flat->address_house_id],
                $flat->flat,
                $flat->flat
            );

        }
    }

    public function commonSyslog(IntercomDevice & CommonInterface $device, IntercomModel $deviceModel): void
    {
        $server = uri(config('syslog_servers')[$deviceModel->syslog]);

        $syslog = $device->getSyslog();

        $newSyslog = clone $syslog;
        $newSyslog->server = $server->getHost();
        $newSyslog->port = $server->getPort() ?: 514;

        if (!$newSyslog->equal($syslog))
            $device->setSyslog($newSyslog);
    }
}