<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision;

use DateInterval;
use DateTime;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Trait\HikVisionTrait;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

class HikVisionIntercom extends IntercomDevice
{
    use HikVisionTrait;

    public string $login = 'admin';

    protected ?array $rfids = null;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->digest($this->login . ':' . $this->password);
    }

    public function getSysInfo(): array
    {
        $response = $this->get('/ISAPI/System/deviceInfo');

        file_logger('intercom')->debug('getSysInfo', [$response]);

        return [
            'DeviceID' => $response['deviceID'],
            'DeviceModel' => $response['model'],
            'HardwareVersion' => $response['hardwareVersion'],
            'SoftwareVersion' => $response['firmwareVersion'] . ' ' . $response['firmwareReleasedDate']
        ];
    }

    public function getSipStatus(): bool
    {
        try {
            $response = $this->get('/ISAPI/System/Network/SIP/1');

            if (!$response)
                return false;

            return collection_get($response, 'Standard.registerStatus', false) == true;
        } catch (Throwable $throwable) {
            file_logger('intercom')->error($throwable);

            return false;
        }
    }

    public function addRfid(string $code, int $apartment): void
    {
        $lastApartment = $this->getLastApartment();

        if ($lastApartment == null) {
            $id = '1';
            $name = (string)$apartment;

            $this->addUser($id, $name);

            $lastApartment = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastApartment['count'] == 5) {
            $id = (string)(intval($lastApartment['id']) + 1);
            $name = (string)$apartment;

            $this->addUser($id, $name);

            $lastApartment = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastApartment == null)
            return;

        $this->post('/ISAPI/AccessControl/CardInfo/Record?format=json', ['CardInfo' => ['employeeNo' => $lastApartment['id'], 'cardNo' => sprintf("%'.010d", hexdec($code)), 'cardType' => 'normalCard']]);
    }

    public function addRfidDeffer(string $code, int $apartment): void
    {
        if ($this->rfids === null)
            $this->rfids = [];

        $this->rfids[] = ['code' => $code, 'apartment' => $apartment];
    }

    public function removeRfid(string $code, int $apartment): void
    {
        $this->put('/ISAPI/AccessControl/CardInfo/Delete?format=json', ['CardInfoDelCond' => ['CardNoList' => [['cardNo' => sprintf("%'.010d", hexdec($code))]]]]);
    }

    public function removeApartment(int $apartment): void
    {
        $this->put('/ISAPI/AccessControl/UserInfo/Delete?format=json', ['UserInfoDelCond' => ['EmployeeNoList' => [['employeeNo' => (string)$apartment]]]]);
    }

    public function setNtp(string $server, int $port, string $timezone = 'Europe/Moscow'): static
    {
        $this->put('/ISAPI/System/time', "<Time><timeMode>NTP</timeMode><timeZone>CST-3:00:00</timeZone></Time>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/time/ntpServers/1', "<NTPServer><id>1</id><addressingFormatType>ipaddress</addressingFormatType><ipAddress>$server</ipAddress><portNo>$port</portNo><synchronizeInterval>60</synchronizeInterval></NTPServer>", ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        $this->put('/ISAPI/System/Network/SIP', "<SIPServerList><SIPServer><id>1</id><Standard><enabled>true</enabled><proxy>$server</proxy><proxyPort>$port</proxyPort><displayName>$login</displayName><userName>$login</userName><authID>$login</authID><password>$password</password><expires>30</expires></Standard></SIPServer></SIPServerList>", ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setSyslog(string $server, int $port): static
    {
        $this->put('/ISAPI/Event/notification/httpHosts', "<HttpHostNotificationList><HttpHostNotification><id>1</id><url>/</url><protocolType>HTTP</protocolType><parameterFormatType>XML</parameterFormatType><addressingFormatType>ipaddress</addressingFormatType><ipAddress>$server</ipAddress><portNo>$port</portNo><httpAuthenticationMethod>none</httpAuthenticationMethod></HttpHostNotification></HttpHostNotificationList>", ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setAudioLevels(array $levels): static
    {
        $levels[0] = array_key_exists(0, $levels) ? $levels[0] : 7;
        $levels[1] = array_key_exists(1, $levels) ? $levels[1] : 7;
        $levels[2] = array_key_exists(2, $levels) ? $levels[1] : 7;

        $this->put('/ISAPI/System/Audio/AudioIn/channels/1', "<AudioIn><id>1</id><AudioInVolumelist><AudioInVlome><type>audioInput</type><volume>$levels[0]</volume></AudioInVlome></AudioInVolumelist></AudioIn>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/Audio/AudioOut/channels/1', "<AudioOut><id>1</id><AudioOutVolumelist><AudioOutVlome><type>audioOutput</type><volume>$levels[1]</volume><talkVolume>$levels[2]</talkVolume></AudioOutVlome></AudioOutVolumelist></AudioOut>", ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setCallTimeout(int $value): static
    {
        $this->put('/ISAPI/VideoIntercom/operationTime', '<OperationTime><maxRingTime>$timeout</maxRingTime></OperationTime>', ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setTalkTimeout(int $value): static
    {
        $this->put('/ISAPI/VideoIntercom/operationTime', '<OperationTime><talkTime>$timeout</talkTime></OperationTime>', ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setUnlockTime(int $time): static
    {
        $this->put('/ISAPI/AccessControl/Door/param/1', "<DoorParam><doorName>Door1</doorName><openDuration>$time</openDuration></DoorParam>", ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function setVideoOverlay(string $title): static
    {
        $this->put('/ISAPI/System/Video/inputs/channels/1', "<VideoInputChannel><id>1</id><inputPort>1</inputPort><name>$title</name></VideoInputChannel>", ['Content-Type' => 'application/xml']);
        $this->put('/ISAPI/System/Video/inputs/channels/1/overlays', '<VideoOverlay><DateTimeOverlay><enabled>true</enabled><positionY>540</positionY><positionX>0</positionX><dateStyle>MM-DD-YYYY</dateStyle><timeStyle>24hour</timeStyle><displayWeek>true</displayWeek></DateTimeOverlay><channelNameOverlay><enabled>true</enabled><positionY>700</positionY><positionX>0</positionX></channelNameOverlay></VideoOverlay>', ['Content-Type' => 'application/xml']);

        return $this;
    }

    public function unlock(bool $value): void
    {
        $this->put('/ISAPI/AccessControl/RemoteControl/door/1', $value ? '<cmd>alwaysOpen</cmd>' : '<cmd>resume</cmd>', ['Content-Type' => 'application/xml']);
    }

    public function open(int $value): void
    {
        $this->put('/ISAPI/AccessControl/RemoteControl/door/' . ($value + 1), '<cmd>open</cmd>', ['Content-Type' => 'application/xml']);
    }

    public function reboot(): void
    {
        $this->put('/ISAPI/System/reboot');
    }

    public function reset(): void
    {
        $this->put('/ISAPI/System/factoryReset', ['mode' => 'basic']);
    }

    public function clearApartment(): void
    {
        foreach ($this->getApartments() as $apartment)
            $this->removeApartment($apartment);
    }

    public function defferRfids(): void
    {
        if ($this->rfids) {
            $lastApartment = $this->getLastApartment();

            if ($lastApartment == null) {
                $id = '1';

                $this->addUser($id, $id);

                sleep(1);

                $lastApartment = ['id' => $id, 'name' => $id, 'count' => 0];
            }

            if ($lastApartment['count'] == 5) {
                $id = (string)(intval($lastApartment['id']) + 1);

                $this->addUser($id, $id);

                sleep(1);

                $lastApartment = ['id' => $id, 'name' => $id, 'count' => 0];
            }

            for ($i = 0; $i < count($this->rfids); $i++) {
                $this->post('/ISAPI/AccessControl/CardInfo/Record?format=json', ['CardInfo' => ['employeeNo' => $lastApartment['id'], 'cardNo' => sprintf("%'.010d", hexdec($this->rfids[$i]['code'])), 'cardType' => 'normalCard']]);

                if ($i + 1 < count($this->rfids) && ++$lastApartment['count'] == 5) {
                    $id = (string)(intval($lastApartment['id']) + 1);

                    $this->addUser($id, $id);

                    sleep(1);

                    $lastApartment = ['id' => $id, 'name' => $id, 'count' => 0];
                }
            }

            $this->rfids = null;
        }
    }

    public function deffer(): void
    {
        $this->defferRfids();
    }

    private function addUser(string $id, string $name): static
    {
        $now = new DateTime();

        $beginTime = $now->format('Y-m-dTH:i:s');
        $endTime = $now->add(new DateInterval('P10Y'))->format('Y-m-dTH:i:s');

        $this->post('/ISAPI/AccessControl/UserInfo/Record?format=json', [
            'UserInfo' => [
                'employeeNo' => $id,
                'name' => $name,
                'userType' => 'normal',
                'localUIRight' => false,
                'maxOpenDoorTime' => 0,
                'Valid' => ['enable' => true, 'beginTime' => $beginTime, 'endTime' => $endTime, 'timeType' => 'local'],
                'doorRight' => '1',
                'RightPlan' => [['doorNo' => 1, 'planTemplateNo' => '1']],
                'roomNumber' => 1,
                'floorNumber' => 0,
                'userVerifyMode' => ''
            ]
        ]);

        return $this;
    }

    private function setUser(string $id, string $name): static
    {
        $now = new DateTime();

        $beginTime = $now->format('Y-m-dTH:i:s');
        $endTime = $now->add(new DateInterval('P10Y'))->format('Y-m-dTH:i:s');

        $this->put('/ISAPI/AccessControl/UserInfo/Modify?format=json', [
            'UserInfo' => [
                'employeeNo' => $id,
                'name' => $name,
                'userType' => 'normal',
                'localUIRight' => false,
                'maxOpenDoorTime' => 0,
                'Valid' => ['enable' => true, 'beginTime' => $beginTime, 'endTime' => $endTime, 'timeType' => 'local'],
                'doorRight' => '1',
                'RightPlan' => [['doorNo' => 1, 'planTemplateNo' => '1']],
                'roomNumber' => 1,
                'floorNumber' => 0,
                'userVerifyMode' => ''
            ]
        ]);

        return $this;
    }

    private function getLastApartment(): ?array
    {
        $page = $this->getApartmentsCount();

        if ($page === 0)
            return null;

        try {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => strval($page), 'maxResults' => 1, 'searchResultPosition' => $page - 1]]);

            if (!array_key_exists('responseStatusStrg', $response) || $response['responseStatusStrg'] !== 'OK')
                return null;

            return [
                'id' => $response['UserInfoSearch']['UserInfo'][0]['employeeNo'],
                'name' => $response['UserInfoSearch']['UserInfo'][0]['name'],

                'count' => $response['UserInfoSearch']['UserInfo'][0]['numOfCard'],
            ];
        } catch (Throwable $throwable) {
            echo $throwable . PHP_EOL;

            return null;
        }
    }

    /**
     * @return int[]
     */
    private function getApartments(): array
    {
        $result = [];
        $pages = $this->getApartmentsCount() / 20 + 1;

        for ($i = 1; $i <= $pages; $i++) {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => '1', 'maxResults' => 20, 'searchResultPosition' => ($i - 1) * 20]]);

            $userInfos = $response['UserInfoSearch']['UserInfo'] ?? [];

            foreach ($userInfos as $userInfo)
                $result[] = intval($userInfo['employeeNo']);
        }

        return $result;
    }

    private function getApartmentsCount(): int
    {
        $response = $this->get('/ISAPI/AccessControl/UserInfo/Count');

        return $response['UserInfoCount']['userNumber'] ?? 0;
    }
}