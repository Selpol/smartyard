<?php

declare(strict_types=1);

namespace Selpol\Task\Tasks\Intercom;

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
use Selpol\Feature\Config\ConfigKey;
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

        $entrance = HouseEntrance::fetch(criteria()->equal('house_domophone_id', $this->id));

        $device = container(DeviceService::class)->intercomByEntity($deviceIntercom);

        if (!$device) {
            throw new KernelException('Не удалось получить устройство');
        }

        $device->pingOrThrow();

        $individualLevels = false;

        $this->setProgress(5);

        if ($device instanceof SipInterface) {
            $this->sip($device, $deviceIntercom);
        }

        $this->setProgress(10);

        if ($device instanceof CommonInterface) {
            $this->common($device, $entrance);
        }

        $this->setProgress(20);

        if ($entrance instanceof HouseEntrance) {
            if (!$entrance->shared && $device instanceof CmsInterface) {
                $this->cms($device, $entrance);
            }

            $this->setProgress(40);

            if ($device instanceof ApartmentInterface) {
                $this->apartment($device, $entrance, $individualLevels);

                foreach ($device->intercom->entrances as $tempEntrance) {
                    $flats = $tempEntrance->flats;

                    if (count($flats) == 0) {
                        continue;
                    }

                    if ($device instanceof KeyHandlerInterface) {
                        $device->handleKey($tempEntrance);
                    } else if ($device instanceof KeyInterface) {
                        $this->key($device, $flats);
                    }

                    if ($device instanceof CodeInterface) {
                        $this->code($device, $flats);
                    }
                }
            }

            if ($device instanceof CommonInterface) {
                if ($entrance->shared) {
                    $this->commonGates($device, $entrance);
                } else {
                    $device->setGates([]);
                    $device->setGatesMode($device->resolver->int(ConfigKey::WicketMode, 1));
                }
            }
        }

        if ($device instanceof CommonInterface) {
            $this->commonOther($device, $individualLevels);
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

        $this->setProgress(100);

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        $this->logger?->error($throwable);
    }

    private function audio(IntercomDevice&AudioInterface $device): void
    {
        $defaultAudioLevels = $device->getDefaultAudioLevels();

        if ($defaultAudioLevels->value === []) {
            return;
        }

        if (!$defaultAudioLevels->equal($device->getAudioLevels())) {
            $device->setAudioLevels($defaultAudioLevels);
        }
    }

    private function video(IntercomDevice&VideoInterface $device, HouseEntrance|null $entrance): void
    {
        $videoEncoding = $device->getVideoEncoding();

        $newVideoEncoding = clone $videoEncoding;

        $newVideoEncoding->quality = $device->resolver->string(ConfigKey::VideoQuality);
        $newVideoEncoding->primaryBitrate = $device->resolver->int(ConfigKey::VideoPrimaryBitrate, 1024);
        $newVideoEncoding->secondaryBitrate = $device->resolver->int(ConfigKey::VideoSecondaryBitrate, 512);

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

        if ($entrance instanceof HouseEntrance) {
            $newVideoOverlay = clone $videoOverlay;
            $newVideoOverlay->title = $device::template($device->resolver->string(ConfigKey::DisplayTitle, $entrance->caller_id), ['entrance' => $entrance->caller_id]);

            if (!$newVideoOverlay->equal($videoOverlay)) {
                $device->setVideoOverlay($newVideoOverlay);
            }
        }
    }

    private function sip(IntercomDevice&SipInterface $device, DeviceIntercom $deviceIntercom): void
    {
        $server = container(SipFeature::class)->sip($deviceIntercom);

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
        $newSipOption->callTimeout = $device->resolver->int(ConfigKey::CleanCallTimeout, 30);
        $newSipOption->talkTimeout = $device->resolver->int(ConfigKey::CleanTalkTimeout, 60);
        $newSipOption->dtmf = [$device->resolver->string(ConfigKey::SipDtmf, '1'), '2'];
        $newSipOption->echo = false;

        if (!$newSipOption->equal($sipOption)) {
            $device->setSipOption($newSipOption);
        }
    }

    public function common(IntercomDevice&CommonInterface $device, HouseEntrance|null $entrance): void
    {
        $ntpServer = $device->resolver->string(ConfigKey::CleanNtp);

        if ($ntpServer != null) {
            $ntpServer = uri($ntpServer);

            $ntp = $device->getNtp();

            $newNtp = clone $ntp;
            $newNtp->server = $ntpServer->getHost();
            $newNtp->port = $ntpServer->getPort() ?? 123;

            $newNtp->timezone = $device->model->isHikVision() ? 'CST-3:00:00' : config('timezone', 'Europe/Moscow');

            if (!$newNtp->equal($ntp)) {
                $device->setNtp($newNtp);

                sleep(1);
            }
        }

        if ($device->mifareKey && $device->mifareSector) {
            $mifare = $device->getMifare();

            $newMifare = clone $mifare;
            $newMifare->enable = true;
            $newMifare->key = $device->mifareKey;
            $newMifare->sector = $device->mifareSector;

            if (!$newMifare->equal($mifare)) {
                $device->setMifare($newMifare);
            }
        }

        $room = $device->getRoom();

        $newRoom = clone $room;
        $newRoom->concierge = $device->resolver->string(ConfigKey::CleanConcierge, '9999');
        $newRoom->sos = $device->resolver->string(ConfigKey::CleanSos, 'SOS');

        if (!$newRoom->equal($room)) {
            $device->setRoom($newRoom);
        }

        if ($entrance instanceof HouseEntrance) {
            $relay = $device->getRelay($entrance->domophone_output ?? 0);

            $newRelay = clone $relay;
            $newRelay->lock = !$entrance->locks_disabled;
            $newRelay->openDuration = $device->resolver->int(ConfigKey::CleanUnlockTime, 5);

            if (!$newRelay->equal($relay)) {
                $device->setRelay($newRelay, $entrance->domophone_output ?? 0);
            }
        }
    }

    /**
     * @param ApartmentInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @param bool &$individualLevels
     * @return void
     */
    public function apartment(IntercomDevice&ApartmentInterface $device, HouseEntrance $entrance, bool &$individualLevels): void
    {
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

        $count = count($houseFlats);

        if ($count === 0) {
            $count = 1;
        }

        $progress = 40;
        $delta = (80 - $progress) / $count;

        foreach ($houseFlats as $flat) {
            if ($device->model->isBeward() && $entrance->shared === 1) {
                continue;
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

            if ($entrance->shared || $blockCall) {
                $numbers = [];
            } else {
                $numbers = [sprintf('1%09d', $flat->house_flat_id)];

                $additional = explode(',', $device->resolver->string(ConfigKey::SipNumber->with_end($flat->flat), ''));

                foreach ($additional as $number) {
                    $numbers[] = $number;
                }
            }

            $apartment = new Apartment(
                intval($flat->flat),
                $entrance->shared == 0 && !$blockCms && $flat->cms_enabled == 1,
                $entrance->shared == 0 && !$blockCall,
                array_key_exists(0, $levels) ? $levels[0] : $device->resolver->int(ConfigKey::ApartmentAnswer, $device->getDefaultAnswerLevel()),
                array_key_exists(1, $levels) ? $levels[1] : $device->resolver->int(ConfigKey::ApartmnetQuiescent, $device->getDefaultQuiescentLevel()),
                $numbers
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

            $progress += $delta;
            $this->setProgress($progress);
        }

        foreach ($apartments as $apartment) {
            $device->removeApartment($apartment);
        }
    }

    /**
     * @param KeyInterface&IntercomDevice $device
     * @param HouseFlat[] $flats
     * @return void
     */
    public function key(IntercomDevice&KeyInterface $device, array $flats): void
    {
        $progress = 80;
        $delta = (90 - $progress) / count($flats);

        /** @var array<string, Key> $keys */
        $keys = array_reduce($device->getKeys(null), static function (array $previous, Key $current) {
            $previous[$current->apartment . $current->key] = $current;

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            $flatKeys = HouseKey::fetchAll(criteria()->equal('access_to', $flat->house_flat_id)->equal('access_type', 2));

            if ($device->debug) {
                $device->getLogger()?->debug('Update flat keys', ['flat' => $flat, 'count' => count($flatKeys)]);
            }

            foreach ($flatKeys as $index => $flatKey) {
                if (array_key_exists($flat->flat . $flatKey->rfid, $keys)) {
                    unset($keys[$flat->flat . $flatKey->rfid]);
                    unset($flatKeys[$index]);

                    continue;
                }

                unset($keys[$flat->flat . $flatKey->rfid]);

                $device->addKey(new Key($flatKey->rfid, intval($flat->flat)));
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
    public function code(IntercomDevice&CodeInterface $device, array $flats): void
    {
        $progress = 90;
        $delta = (95 - $progress) / count($flats);

        /** @var array<int, Code[]> $apartmentCodes */
        $apartmentCodes = array_reduce($device->getCodes(null), static function (array $previous, Code $current) {
            if (!array_key_exists($current->apartment, $previous)) {
                $previous[$current->apartment] = [];
            }

            $previous[$current->apartment][] = $current;

            return $previous;
        }, []);

        foreach ($flats as $flat) {
            $code = intval($flat->open_code) !== 0 ? intval($flat->open_code) : null;

            if ($code !== null) {
                if (array_key_exists($flat->flat, $apartmentCodes)) {
                    if (count($apartmentCodes[$flat->flat]) == 1) {
                        if ($apartmentCodes[$flat->flat][0]->code != $code) {
                            $device->removeCode($apartmentCodes[$flat->flat][0]);
                        } else {
                            continue;
                        }
                    } else {
                        foreach ($apartmentCodes[$flat->flat] as $code) {
                            $device->removeCode($code);
                        }
                    }
                }

                $device->addCode(new Code(is_int($code) ? $code : $code->code, intval($flat->flat)));
            } elseif (array_key_exists($flat->flat, $apartmentCodes)) {
                foreach ($apartmentCodes[$flat->flat] as $code) {
                    $device->removeCode($code);
                }
            }

            $progress += $delta;
            $this->setProgress($progress);
        }
    }

    /**
     * @param ApartmentInterface&CmsInterface&IntercomDevice $device
     * @param HouseEntrance $entrance
     * @return void
     */
    public function cms(IntercomDevice&ApartmentInterface&CmsInterface $device, HouseEntrance $entrance): void
    {
        $entranceLevels = array_map(static fn(string $value): int => intval($value), array_filter(explode(',', $entrance->cms_levels ?? ''), static fn(string $value): bool => $value !== ''));

        $levels = $device->getCmsLevels();

        $newLevels = clone $levels;

        $newLevels->value[0] = array_key_exists(0, $entranceLevels) ? $entranceLevels[0] : $device->resolver->int(ConfigKey::ApartmentAnswer, $device->getDefaultAnswerLevel());
        $newLevels->value[1] = array_key_exists(1, $entranceLevels) ? $entranceLevels[1] : $device->resolver->int(ConfigKey::ApartmnetQuiescent, $device->getDefaultQuiescentLevel());

        if (!$newLevels->equal($levels)) {
            $device->setCmsLevels($newLevels);
        }

        $models = $device->getCmsModels();
        $cmsModel = array_key_exists(strtoupper($entrance->cms), $models) ? $models[strtoupper($entrance->cms)] : false;

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
    public function commonGates(IntercomDevice&CommonInterface $device, HouseEntrance $entrance): void
    {
        /** @var Gate[] $gates */
        $gates = [];

        $housesEntrances = container(DatabaseService::class)->get('SELECT addresses_houses.house_full, houses_houses_entrances.address_house_id, prefix FROM houses_houses_entrances JOIN public.addresses_houses ON addresses_houses.address_house_id = houses_houses_entrances.address_house_id WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]);

        $flatsIds = array_map(static fn(array $flatId) => $flatId['house_flat_id'], container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]));

        if ($flatsIds === []) {
            return;
        }

        foreach ($housesEntrances as $housesEntrance) {
            $firstFlat = HouseFlat::fetch(criteria()->in('house_flat_id', $flatsIds)->equal('address_house_id', $housesEntrance['address_house_id'])->asc('cast(flat AS INTEGER)')->limit(1));
            $lastFlat = HouseFlat::fetch(criteria()->in('house_flat_id', $flatsIds)->equal('address_house_id', $housesEntrance['address_house_id'])->desc('cast(flat AS INTEGER)')->limit(1));

            if ($firstFlat == null) {
                continue;
            }

            if ($lastFlat == null) {
                continue;
            }

            $entrancesFlatIds = array_map(static fn(array $value) => $value['house_entrance_id'], container(DatabaseService::class)->get('SELECT house_entrance_id FROM houses_entrances_flats WHERE house_flat_id = :first_id OR house_flat_id = :last_id', ['first_id' => $firstFlat->house_flat_id, 'last_id' => $lastFlat->house_flat_id]));
            $entrancesFlat = HouseEntrance::fetch(criteria()->in('house_entrance_id', $entrancesFlatIds)->equal('shared', 0)->simple('house_entrance_id', '!=', $entrance->house_entrance_id)->limit(1));

            if ($entrancesFlat == null) {
                continue;
            }

            $segments = explode(', ', $housesEntrance['house_full']);

            if (str_starts_with($segments[0], 'г ') || str_ends_with($segments[0], ' обл')) {
                unset($segments[0]);
            }

            $gates[] = new Gate(implode(', ', $segments), $housesEntrance['prefix'], intval($firstFlat->flat), intval($lastFlat->flat));
        }

        $device->setGates($gates);
    }

    public function commonOther(IntercomDevice&CommonInterface $device, bool $individualLevels): void
    {
        $server = uri($device->resolver->string(ConfigKey::CleanSyslog, 'syslog://127.0.0.1:514'));

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

        $device->setServiceMode(false);
    }
}
