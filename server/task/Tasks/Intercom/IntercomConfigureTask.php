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

        $entrances = HouseEntrance::fetchAll(criteria()->equal('house_domophone_id', $this->id)->equal('domophone_output', 0));

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

        $this->setProgress(6);

        if ($device instanceof VideoInterface) {
            $this->video($device, $entrances);
        }

        $this->setProgress(10);

        if ($device instanceof SipInterface) {
            $this->sip($device, $deviceIntercom, $clean);
        }

        $this->setProgress(20);

        if ($device instanceof CommonInterface) {
            $this->common($device, $entrances, $clean, $ntp);
        }

        if ($entrances !== []) {
            $this->setProgress(30);

            if ($device instanceof CmsInterface) {
                $this->cms($device, $entrances);
            }

            $this->setProgress(50);

            if ($device instanceof ApartmentInterface) {
                /** @var array<int, HouseFlat> $flats */
                $flats = [];

                $this->apartment($device, $entrances, $flats);

                if ($device instanceof KeyHandlerInterface) {
                    $device->handleKey($flats, $entrances);
                } elseif (count($flats) > 0) {
                    $this->setProgress(80);
                    if ($device instanceof KeyInterface) {
                        $this->key($device, $flats);
                    }
                }

                $this->setProgress(90);

                if (count($flats) > 0) {
                    if ($device instanceof CodeInterface) {
                        $this->code($device, $flats);
                    }

                    $this->setProgress(95);

                    if ($device instanceof CommonInterface && $entrances[0]->shared) {
                        $this->commonGates($device, $entrances[0], $flats);
                    }
                }
            }
        }

        $this->setProgress(98);

        if ($device instanceof CommonInterface) {
            $this->commonOther($device, $deviceModel);
        }

        if ($deviceIntercom->first_time == 0) {
            if ($device instanceof AudioInterface) {
                $this->audio($device);
            }

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

    /**
     * @param VideoInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @return void
     */
    private function video(IntercomDevice & VideoInterface $device, array $entrances): void
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

        $newVideoOverlay = clone $videoOverlay;
        $newVideoOverlay->title = $entrances[0]->caller_id;

        if (!$newVideoOverlay->equal($videoOverlay)) {
            $device->setVideoOverlay($newVideoOverlay);
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

    /**
     * @param CommonInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @param IntercomClean $clean
     * @param array $ntpServer
     * @return void
     */
    public function common(IntercomDevice & CommonInterface $device, array $entrances, IntercomClean $clean, array $ntpServer): void
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

        if ($entrances !== []) {
            foreach ($entrances as $entrance) {
                $relay = $device->getRelay($entrance->domophone_output ?? 0);

                $newRelay = clone $relay;
                $newRelay->lock = !$entrance->locks_disabled;
                $newRelay->openDuration = $clean->unlockTime;

                if (!$newRelay->equal($relay)) {
                    $device->setRelay($newRelay, $entrance->domophone_output ?? 0);
                }
            }
        }

        if (!$device->getIndividualLevels()) {
            $device->setIndividualLevels(true);
        }

        if ($device->getAutoCollectKey()) {
            $device->setAutoCollectKey(false);
        }
    }

    /**
     * @param CmsInterface&IntercomDevice $device
     * @param HouseEntrance[] $entrances
     * @return void
     */
    public function cms(IntercomDevice & CmsInterface $device, array $entrances): void
    {
        if ($entrances[0]->shared) {
            return;
        }

        $cmsModel = array_key_exists(strtoupper($entrances[0]->cms), $device->model->cmsesMap) ? $device->model->cmsesMap[strtoupper($entrances[0]->cms)] : false;

        if ($device->getCmsModel() != $cmsModel) {
            $device->setCmsModel($entrances[0]->cms);
        }

        $cms = container(HouseFeature::class)->getCms($entrances[0]->house_entrance_id);

        foreach ($cms as $item) {
            $device->setCmsApartmentDeffer(new CmsApartment($item['cms'] + 1, $item['dozen'], intval($item['unit']), $item['apartment']));
        }

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

        foreach ($entrances as $entrance) {
            $entrancesFlats = container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]);

            $houseFlats = HouseFlat::fetchAll(criteria()->in('house_flat_id', array_map(static fn(array $flat) => $flat['house_flat_id'], $entrancesFlats)));

            if ($houseFlats === []) {
                continue;
            }

            $entranceLevels = array_map(static fn(string $value): int => intval($value), array_filter(explode(',', $entrance->cms_levels ?? ''), static fn(string $value): bool => $value !== ''));

            foreach ($houseFlats as $flat) {
                if (!array_key_exists(intval($flat->flat), $flats)) {
                    $flats[intval($flat->flat)] = $flat;
                }

                $flatEntrance = container(DatabaseService::class)->get('SELECT apartment, cms_levels FROM houses_entrances_flats WHERE house_entrance_id = :entrance_id AND house_flat_id = :flat_id', ['entrance_id' => $entrance->house_entrance_id, 'flat_id' => $flat->house_flat_id]);

                if (!$flatEntrance) {
                    throw new DeviceException($device, 'Вход к квартире не привязан');
                }

                $levels = array_map(static fn(string $value): int => intval($value), array_filter(explode(',', $flatEntrance['cms_levels'] ?? ''), static fn(string $value): bool => $value !== ''));

                $blockCms = container(BlockFeature::class)->getFirstBlockForFlat($flat->house_flat_id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]) != null;
                $blockCall = container(BlockFeature::class)->getFirstBlockForFlat($flat->house_flat_id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]) != null;

                $apartment = new Apartment(
                    intval($flat->flat),
                    $entrance->shared == 0 && !$blockCms && $flat->cms_enabled == 1,
                    $entrance->shared == 0 && !$blockCall,
                    array_key_exists(0, $levels) ? $levels[0] : (array_key_exists(0, $entranceLevels) ? $entranceLevels[0] : ($device->model->vendor === 'BEWARD' ? 330 : ($device->model->vendor === 'IS' ? 255 : null))),
                    array_key_exists(1, $levels) ? $levels[1] : (array_key_exists(1, $entranceLevels) ? $entranceLevels[1] : ($device->model->vendor === 'BEWARD' ? 530 : ($device->model->vendor === 'IS' ? 255 : null))),
                    ($entrance->shared == 1 || $blockCall) ? [] : [sprintf('1%09d', $flat->house_flat_id)]
                );

                if (array_key_exists(intval($flat->flat), $apartments)) {
                    if ($device->model->vendor === 'BEWARD') {
                        if (!$apartment->equal($apartments[intval($flat->flat)])) {
                            $device->setApartment($apartment);
                        }
                    } elseif (!$apartment->equalWithoutNumbers($apartments[intval($flat->flat)])) {
                        $device->setApartment($apartment);
                    }

                    unset($apartments[intval($flat->flat)]);
                } else {
                    $device->addApartment($apartment);
                }
            }

            $progress += $delta;
            $this->setProgress($progress);
        }

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
     * @param CommonInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @param array<int, HouseFlat> $flats
     * @return void
     */
    public function commonGates(IntercomDevice & CommonInterface $device, HouseEntrance $entrance, array $flats): void
    {
        usort($flats, static fn(HouseFlat $a, HouseFlat $b): int => $a->flat <=> $b->flat);

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
         * @var array<int, string> $intercoms
         */
        $intercoms = [];

        /** @var Gate[] $gates */
        $gates = [];

        $pivotCriteria = criteria()->in('house_flat_id', array_map(static fn(HouseFlat $flat) => $flat->house_flat_id, $flats));
        $pivot = container(DatabaseService::class)->get('SELECT house_entrance_id, house_flat_id FROM houses_entrances_flats' . $pivotCriteria->getSqlString(), $pivotCriteria->getSqlParams());

        $pivot = array_reduce($pivot, static function (array $previous, array $current) {
            if (!array_key_exists($current['house_flat_id'], $previous)) {
                $previous[$current['house_flat_id']] = [];
            }

            $previous[$current['house_flat_id']][] = $current['house_entrance_id'];

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            if (!array_key_exists($flat->house_flat_id, $pivot)) {
                continue;
            }

            if (!array_key_exists($flat->address_house_id, $prefixes)) {
                $prefixes[$flat->address_house_id] = container(DatabaseService::class)->get('SELECT prefix FROM houses_houses_entrances WHERE address_house_id = :house_id AND house_entrance_id = :entrance_id', ['house_id' => $flat->address_house_id, 'entrance_id' => $entrance->house_entrance_id], options: ['singlify'])['prefix'];
            }

            /** @var HouseEntrance|null $flatEntrance */
            $flatEntrance = null;

            foreach ($pivot[$flat->house_flat_id] as $entranceId) {
                if (array_key_exists($entranceId, $entrances)) {
                    $flatEntrance = $entrances[$entranceId];

                    break;
                }

                $temp = HouseEntrance::findById($entranceId, criteria()->equal('shared', 0));

                if (!$temp instanceof HouseEntrance) {
                    continue;
                }

                $entrances[$entranceId] = $temp;
                $flatEntrance = $temp;

                break;
            }

            if (!$flatEntrance) {
                continue;
            }

            if (!array_key_exists($flatEntrance->house_domophone_id, $intercoms)) {
                $intercoms[$entrance->house_domophone_id] = DeviceIntercom::findById($entrance->house_domophone_id, setting: setting()->columns(['ip']))->ip;
            }

            if (count($gates) > 0 && $gates[count($gates) - 1]->end + 1 == $flat->flat) {
                ++$gates[count($gates) - 1]->end;
            } else {
                $gates[] = new Gate($intercoms[$flatEntrance->house_domophone_id], $prefixes[$flat->address_house_id], $flat->flat, $flat->flat);
            }
        }

        $device->setGates($gates);
    }

    public function commonOther(IntercomDevice & CommonInterface $device, IntercomModel $deviceModel): void
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

        if (!$newDDns->equal($dDns)) {
            $device->setDDns($newDDns);
        }

        if ($device->getUPnP()) {
            $device->setUPnP(false);
        }
    }
}