<?php

namespace Selpol\Task\Tasks\Intercom;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\Gate;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
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
            $apartments = $this->apartment($device, $entrances);

            $this->setProgress(80);

            if ($device instanceof KeyInterface)
                $this->key($device, $apartments);

            $this->setProgress(90);

            if ($device instanceof CodeInterface)
                $this->code($device, $apartments);
        }

        $this->setProgress(95);

        if ($device instanceof CommonInterface) {
            if ($entrances[0]->shared)
                $this->commonGates($device);

            $this->commonSyslog($device, $ntp);
        }

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
        $newSip->port = 5060;

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
            $newMifare->sector = $sector;

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
     * @return Apartment[]
     */
    public function apartment(IntercomDevice & ApartmentInterface $device, array $entrances): array
    {
        foreach ($entrances as $entrance) {
            $ids = container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :entrance_id', ['entrance_id' => $entrance->house_entrance_id]);
            $flats = HouseFlat::fetchAll(criteria()->in('house_flat_id', $ids));

            if (!$flats)
                continue;
        }

        return [];
    }
//            foreach ($flats as $flat) {
//                $apartment = $flat['flat'];
//                $apartment_levels = $cms_levels;
//
//                $flat_entrances = array_filter($flat['entrances'], static fn($entrance) => $entrance['domophoneId'] == $domophoneId);
//
//                if ($flat_entrances && count($flat_entrances) > 0) {
//                    foreach ($flat_entrances as $flat_entrance) {
//                        if (isset($flat_entrance['apartmentLevels']) && $flat_entrance['apartmentLevels']) {
//                            $apartment_levels = array_map('intval', explode(',', $flat_entrance['apartmentLevels']));
//                        }
//
//                        if ($flat_entrance['apartment'] != 0 && $flat_entrance['apartment'] != $apartment) {
//                            $apartment = $flat_entrance['apartment'];
//                        }
//                    }
//
//                    $block = container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CMS]) != null;
//
//                    $device->addApartmentDeffer(
//                        $apartment + $offset,
//                        $is_shared ? false : ($block ? false : $flat['cmsEnabled']),
//                        $is_shared ? [] : [sprintf('1%09d', $flat['flatId'])],
//                        $apartment_levels,
//                        intval($flat['openCode']) ?? 0
//                    );
//
//                    $keys = container(HouseFeature::class)->getKeys('flatId', $flat['flatId']);
//
//                    foreach ($keys as $key)
//                        $device->addRfidDeffer($key['rfId'], $apartment);
//                }
//
//                if ($flat['flat'] == $end)
//                    $offset += $flat['flat'];
//            }

    /**
     * @param KeyInterface&IntercomDevice $device
     * @param Apartment[] $apartments
     * @return void
     */
    public function key(IntercomDevice & KeyInterface $device, array $apartments): void
    {

    }

    /**
     * @param CodeInterface&IntercomDevice $device
     * @param Apartment[] $apartments
     * @return void
     */
    public function code(IntercomDevice & CodeInterface $device, array $apartments): void
    {

    }

    /**
     * @param CommonInterface&IntercomDevice $device
     * @return void
     */
    public function commonGates(IntercomDevice & CommonInterface $device): void
    {
//        $gates = [];
//        $currentGates = $device->getGates();
//
//        $update = false;
//
//        if (count($currentGates) !== count($gates))
//            $update = true;
//        else for ($i = 0; $i < count($gates); $i++) {
//            if (!$gates[$i]->equal($currentGates[$i])) {
//                $update = true;
//                break;
//            }
//        }
//
//        if ($update)
//            $device->setGates($gates);
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