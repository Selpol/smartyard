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
use Selpol\Device\Ip\Intercom\Setting\IntercomClean;
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
use Throwable;

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
        $deviceIntercom = DeviceIntercom::findById($this->id, setting: setting()->nonNullable());
        $deviceModel = IntercomModel::model($deviceIntercom->model);

        if (!$deviceIntercom instanceof DeviceIntercom || !$deviceModel instanceof IntercomModel) {
            throw new KernelException('Устройство не существует');
        }

        $this->setProgress(1);

        $entrance = HouseEntrance::fetch(criteria()->equal('house_domophone_id', $this->id));

        $this->setProgress(2);

        $device = container(DeviceService::class)->intercomByEntity($deviceIntercom);

        if (!$device) {
            throw new KernelException('Не удалось получить устройство');
        }

        if (!$device->ping()) {
            throw new DeviceException($device, 'Устройство не доступно');
        }

        $this->setProgress(5);

        $clean = $device->getIntercomClean();
        $ntp = $device->getIntercomNtp();

        $individualLevels = false;

        $this->setProgress(10);

        if ($device instanceof SipInterface) {
            $this->sip($device, $deviceIntercom, $clean);
        }

        $this->setProgress(20);

        if ($device instanceof CommonInterface) {
            $this->common($device, $entrance, $clean, $ntp);
        }

        if ($entrance !== null) {
            $this->setProgress(30);

            if ($device instanceof CmsInterface) {
                $this->cms($device, $entrance);
            }

            if ($device instanceof ApartmentInterface) {
                /** @var array<int, HouseFlat> $flats */
                $flats = [];

                $this->apartment($device, $entrance, $flats, $individualLevels);

                if ($device instanceof KeyHandlerInterface) {
                    $device->handleKey($flats, $entrance);
                } elseif (count($flats) > 0) {
                    $this->setProgress(60);
                    if ($device instanceof KeyInterface) {
                        $this->key($device, $flats);
                    }
                }

                $this->setProgress(70);

                if (count($flats) > 0) {
                    if ($device instanceof CodeInterface) {
                        $this->code($device, $flats);
                    }

                    $this->setProgress(75);
                }
            }

            if ($device instanceof CommonInterface && $entrance->shared) {
                $this->commonGates($device, $entrance);
            }
        }

        $this->setProgress(85);

        if ($device instanceof CommonInterface) {
            $this->commonOther($device, $deviceModel, $individualLevels);
        }

        if ($deviceIntercom->first_time == 0 && $device instanceof AudioInterface) {
            $this->audio($device);
        }

        if ($device instanceof VideoInterface) {
            $this->video($device, $entrance);
        }

        if ($deviceIntercom->first_time == 0) {
            $deviceIntercom->first_time = 1;

            $deviceIntercom->update();
            $deviceIntercom->refresh();
        }

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        $this->logger?->error($throwable);
    }

    private function audio(IntercomDevice & AudioInterface $device): void
    {
        $defaultAudioLevels = $device->getDefaultAudioLevels();

        if ($defaultAudioLevels->value === []) {
            return;
        }

        if (!$defaultAudioLevels->equal($device->getAudioLevels())) {
            $device->setAudioLevels($defaultAudioLevels);
        }
    }

    private function video(IntercomDevice & VideoInterface $device, HouseEntrance|null $entrance): void
    {
        $videoEncoding = $device->getVideoEncoding();

        $newVideoEncoding = clone $videoEncoding;
        $newVideoEncoding->primaryBitrate = $device->model->primaryBitrate;
        $newVideoEncoding->secondaryBitrate = $device->model->secondaryBitrate;

        if (!$newVideoEncoding->equal($videoEncoding)) {
            $device->setVideoEncoding($newVideoEncoding);
        }

        $videoDetection = $device->getVideoDetection();

        $newVideoDetection = clone $videoDetection;
        $newVideoDetection->enable = true;

        if (!$newVideoDetection->equal($videoDetection)) {
            $device->setVideoDetection($newVideoDetection);
        }

        $videoOverlay = $device->getVideoOverlay();

        if ($entrance !== null) {
            $newVideoOverlay = clone $videoOverlay;
            $newVideoOverlay->title = $entrance->caller_id;

            if (!$newVideoOverlay->equal($videoOverlay)) {
                $device->setVideoOverlay($newVideoOverlay);
            }
        }
    }

    private function sip(IntercomDevice & SipInterface $device, DeviceIntercom $deviceIntercom, IntercomClean $clean): void
    {
        $server = container(SipFeature::class)->server('ip', $deviceIntercom->server)[0];

        $sip = $device->getSip();

        $newSip = clone $sip;
        $newSip->login = sprintf("1%05d", $deviceIntercom->house_domophone_id);
        $newSip->password = $device->password;
        $newSip->server = $server->internal_ip;
        $newSip->port = $server->internal_port;

        if (!$newSip->equal($sip)) {
            $device->setSip($newSip);
        }

        $sipOption = $device->getSipOption();

        $newSipOption = clone $sipOption;
        $newSipOption->callTimeout = $clean->callTimeout;
        $newSipOption->talkTimeout = $clean->talkTimeout;
        $newSipOption->dtmf = [$deviceIntercom->dtmf, '2'];
        $newSipOption->echo = false;

        if (!$newSipOption->equal($sipOption)) {
            $device->setSipOption($newSipOption);
        }
    }

    public function common(IntercomDevice & CommonInterface $device, HouseEntrance|null $entrance, IntercomClean $clean, array $ntpServer): void
    {
        $ntp = $device->getNtp();

        $newNtp = clone $ntp;
        $newNtp->server = $ntpServer[0];
        $newNtp->port = $ntpServer[1];
        $newNtp->timezone = config('timezone', 'Europe/Moscow');

        if (!$newNtp->equal($ntp)) {
            $device->setNtp($newNtp);
        }

        $key = env('MIFARE_KEY');
        $sector = env('MIFARE_SECTOR');

        if ($key && $sector) {
            $mifare = $device->getMifare();

            $newMifare = clone $mifare;
            $newMifare->enable = true;
            $newMifare->key = $key;
            $newMifare->sector = intval($sector);

            if (!$newMifare->equal($mifare)) {
                $device->setMifare($newMifare);
            }
        }

        $room = $device->getRoom();

        $newRoom = clone $room;
        $newRoom->concierge = $clean->concierge;
        $newRoom->sos = $clean->sos;

        if (!$newRoom->equal($room)) {
            $device->setRoom($newRoom);
        }

        if ($entrance !== null) {
            $relay = $device->getRelay($entrance->domophone_output ?? 0);

            $newRelay = clone $relay;
            $newRelay->lock = !$entrance->locks_disabled;
            $newRelay->openDuration = $clean->unlockTime;

            if (!$newRelay->equal($relay)) {
                $device->setRelay($newRelay, $entrance->domophone_output ?? 0);
            }
        }
    }

    /**
     * @param ApartmentInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @param array<int, HouseFlat> $flats
     * @param bool &$individualLevels
     * @return void
     */
    public function apartment(IntercomDevice & ApartmentInterface $device, HouseEntrance $entrance, array &$flats, bool &$individualLevels): void
    {
        $this->setProgress(50);

        /** @var array<int, Apartment> $apartments */
        $apartments = array_reduce($device->getApartments(), static function (array $previous, Apartment $current) {
            $previous[$current->apartment] = $current;

            return $previous;
        }, []);

        $entrancesFlats = container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]);

        $houseFlats = HouseFlat::fetchAll(criteria()->in('house_flat_id', array_map(static fn(array $flat) => $flat['house_flat_id'], $entrancesFlats))->asc('cast(flat AS INTEGER)'));

        if ($houseFlats === []) {
            return;
        }

        foreach ($houseFlats as $flat) {
            if (!array_key_exists(intval($flat->flat), $flats)) {
                $flats[intval($flat->flat)] = $flat;
            }

            $flatEntrances = container(DatabaseService::class)->get('SELECT cms_levels FROM houses_entrances_flats WHERE house_entrance_id = :entrance_id AND house_flat_id = :flat_id', ['entrance_id' => $entrance->house_entrance_id, 'flat_id' => $flat->house_flat_id]);

            if ($flatEntrances === []) {
                continue;
            }

            $levels = array_map(static fn(string $value): int => intval($value), array_filter(explode(',', $flatEntrances[0]['cms_levels'] ?? ''), static fn(string $value): bool => $value !== ''));

            if (!$individualLevels && $levels !== []) {
                $individualLevels = true;
            }

            $blockCms = container(BlockFeature::class)->getFirstBlockForFlat($flat->house_flat_id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]) != null;
            $blockCall = container(BlockFeature::class)->getFirstBlockForFlat($flat->house_flat_id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]) != null;

            $apartment = new Apartment(
                intval($flat->flat),
                $entrance->shared == 0 && !$blockCms && $flat->cms_enabled == 1,
                $entrance->shared == 0 && !$blockCall,
                array_key_exists(0, $levels) ? $levels[0] : ($device->model->vendor === 'BEWARD' ? 330 : ($device->model->vendor === 'IS' ? 255 : null)),
                array_key_exists(1, $levels) ? $levels[1] : ($device->model->vendor === 'BEWARD' ? 530 : ($device->model->vendor === 'IS' ? 255 : null)),
                ($entrance->shared == 1 || $blockCall) ? [] : [sprintf('1%09d', $flat->house_flat_id)]
            );

            if (array_key_exists($apartment->apartment, $apartments)) {
                if ($device->model->vendor === 'BEWARD') {
                    if (!$apartment->equal($apartments[$apartment->apartment])) {
                        $device->setApartment($apartment);
                    }
                } elseif (!$apartment->equalWithoutNumbers($apartments[$apartment->apartment])) {
                    $device->setApartment($apartment);
                }

                unset($apartments[$apartment->apartment]);
            } else {
                $device->addApartment($apartment);
            }
        }

        $this->setProgress(80);

        foreach ($apartments as $apartment) {
            $device->removeApartment($apartment);
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

        /** @var array<string, Key> $keys */
        $keys = array_reduce($device->getKeys(null), static function (array $previous, Key $current) {
            $previous[$current->apartment . $current->key] = $current;

            return $previous;
        }, []);

        foreach ($flats as $apartment => $flat) {
            $flatKeys = HouseKey::fetchAll(criteria()->equal('access_to', $flat->house_flat_id)->equal('access_type', 2));

            foreach ($flatKeys as $index => $flatKey) {
                if (array_key_exists($flat->flat . $flatKey->rfid, $keys)) {
                    unset($keys[$flat->flat . $flatKey->rfid]);
                    unset($flatKeys[$index]);

                    continue;
                }

                unset($keys[$flat->flat . $flatKey->rfid]);

                $device->addKey(new Key($flatKey->rfid, $apartment));
            }

            $progress += $delta;
            $this->setProgress($progress);
        }

        foreach ($keys as $key) {
            $device->removeKey($key);
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

        /** @var array<int, Code[]> $apartmentCodes */
        $apartmentCodes = array_reduce($device->getCodes(null), static function (array $previous, Code $current) {
            if (!array_key_exists($current->apartment, $previous)) {
                $previous[$current->apartment] = [];
            }

            $previous[$current->apartment][] = $current;

            return $previous;
        }, []);

        foreach ($flats as $apartment => $flat) {
            $code = intval($flat->open_code) !== 0 ? intval($flat->open_code) : null;

            if ($code !== null) {
                if (array_key_exists($apartment, $apartmentCodes)) {
                    if (count($apartmentCodes[$apartment]) == 1) {
                        if ($apartmentCodes[$apartment][0]->code != $code) {
                            $device->removeCode($apartmentCodes[$apartment][0]);
                        } else {
                            continue;
                        }
                    } else {
                        foreach ($apartmentCodes[$apartment] as $code) {
                            $device->removeCode($code);
                        }
                    }
                }

                $device->addCode(new Code($code, $apartment));
            } elseif (array_key_exists($apartment, $apartmentCodes)) {
                foreach ($apartmentCodes[$apartment] as $code) {
                    $device->removeCode($code);
                }
            }

            $progress += $delta;
            $this->setProgress($progress);
        }
    }

    /**
     * @param CmsInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @return void
     */
    public function cms(IntercomDevice & CmsInterface $device, HouseEntrance $entrance): void
    {
        if ($entrance->shared) {
            return;
        }

        $entranceLevels = array_map(static fn(string $value): int => intval($value), array_filter(explode(',', $entrance->cms_levels ?? ''), static fn(string $value): bool => $value !== ''));

        $levels = $device->getCmsLevels();

        $newLevels = clone $levels;

        $newLevels->value[0] = array_key_exists(0, $entranceLevels) ? $entranceLevels[0] : ($device->model->vendor === 'BEWARD' ? 330 : ($device->model->vendor === 'IS' ? 255 : null));
        $newLevels->value[1] = array_key_exists(1, $entranceLevels) ? $entranceLevels[1] : ($device->model->vendor === 'BEWARD' ? 330 : ($device->model->vendor === 'IS' ? 255 : null));

        if (!$newLevels->equal($levels)) {
            $device->setCmsLevels($newLevels);
        }

        $cmsModel = array_key_exists(strtoupper($entrance->cms), $device->model->cmsesMap) ? $device->model->cmsesMap[strtoupper($entrance->cms)] : false;

        if ($device->getCmsModel() != $cmsModel) {
            $device->setCmsModel($entrance->cms);
        }

        $cms = container(HouseFeature::class)->getCms($entrance->house_entrance_id);

        foreach ($cms as $item) {
            $device->setCmsApartmentDeffer(new CmsApartment($item['cms'] + 1, $item['dozen'], intval($item['unit']), $item['apartment']));
        }

        $device->defferCms();
    }

    /**
     * @param CommonInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @return void
     */
    public function commonGates(IntercomDevice & CommonInterface $device, HouseEntrance $entrance): void
    {
        /** @var Gate[] $gates */
        $gates = [];

        $housesEntrances = container(DatabaseService::class)->get('SELECT address_house_id, prefix FROM houses_houses_entrances WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]);

        $flatsIds = array_map(static fn(array $flatId) => $flatId['house_flat_id'], container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]));

        if ($flatsIds === []) {
            return;
        }

        foreach ($housesEntrances as $housesEntrance) {
            $firstFlat = HouseFlat::fetch(criteria()->in('house_flat_id', $flatsIds)->equal('address_house_id', $housesEntrance['address_house_id'])->asc('cast(flat AS INTEGER)')->limit(1));
            $lastFlat = HouseFlat::fetch(criteria()->in('house_flat_id', $flatsIds)->equal('address_house_id', $housesEntrance['address_house_id'])->desc('cast(flat AS INTEGER)')->limit(1));

            if ($firstFlat == null || $lastFlat == null) {
                continue;
            }

            $entrancesFlatIds = array_map(static fn(array $value) => $value['house_entrance_id'], container(DatabaseService::class)->get('SELECT house_entrance_id FROM houses_entrances_flats WHERE house_flat_id = :first_id OR house_flat_id = :last_id', ['first_id' => $firstFlat->house_flat_id, 'last_id' => $lastFlat->house_flat_id]));
            $entrancesFlat = HouseEntrance::fetch(criteria()->in('house_entrance_id', $entrancesFlatIds)->equal('shared', 0)->simple('house_entrance_id', '!=', $entrance->house_entrance_id)->limit(1));

            if ($entrancesFlat == null) {
                continue;
            }

            $intercom = DeviceIntercom::findById($entrancesFlat->house_domophone_id, setting: setting()->columns(['ip']));

            $gates[] = new Gate($intercom->ip, $housesEntrance['prefix'], intval($firstFlat->flat), intval($lastFlat->flat));
        }

        $device->setGates($gates);
    }

    public function commonOther(IntercomDevice & CommonInterface $device, IntercomModel $deviceModel, bool $individualLevels): void
    {
        $urls = config('syslog_servers')[$deviceModel->syslog];

        $server = uri($urls[array_rand($urls)]);

        $syslog = $device->getSyslog();

        $newSyslog = clone $syslog;
        $newSyslog->server = $server->getHost();
        $newSyslog->port = $server->getPort() !== null && $server->getPort() !== 0 ? $server->getPort() : 514;

        if (!$newSyslog->equal($syslog)) {
            $device->setSyslog($newSyslog);
        }

        $dDns = $device->getDDns();

        $newDDns = clone $dDns;
        $newDDns->enable = false;

        if ($device->getIndividualLevels() !== $individualLevels) {
            $device->setIndividualLevels($individualLevels);
        }

        if ($device->getAutoCollectKey()) {
            $device->setAutoCollectKey(false);
        }

        if (!$newDDns->equal($dDns)) {
            $device->setDDns($newDDns);
        }

        if ($device->getUPnP()) {
            $device->setUPnP(false);
        }
    }
}