<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use DateInterval;
use DateTime;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Service\DatabaseService;
use Throwable;

trait KeyTrait
{
    /**
     * @param int|null $apartment
     * @return Key[]
     * @return Key[]
     */
    public function getKeys(?int $apartment): array
    {
        $result = [];

        $response = $this->post('/ISAPI/AccessControl/CardInfo/Search?format=json', ['CardInfoSearchCond' => ['searchID' => '1', 'maxResults' => 30, 'searchResultPosition' => 0]]);

        $process = function (array $response) use (&$result): void {
            $cardInfos = $response['CardInfoSearch']['CardInfo'] ?? [];

            foreach ($cardInfos as $cardInfo) {
                $result[] = new Key(strtoupper(sprintf("%'.014s", dechex(intval($cardInfo['cardNo'])))), intval($cardInfo['employeeNo']));
            }
        };

        $process($response);

        $pages = (int)ceil($response['CardInfoSearch']['totalMatches'] / 30);

        for ($i = 2; $i < $pages; ++$i) {
            $response = $this->post('/ISAPI/AccessControl/CardInfo/Search?format=json', ['CardInfoSearchCond' => ['searchID' => '1', 'maxResults' => 30, 'searchResultPosition' => ($i - 1) * 30]]);

            $process($response);
        }

        return $result;
    }

    public function addKey(Key $key): void
    {
        $lastUser = $this->getLastUser();

        if ($lastUser == null) {
            $id = '1';
            $name = date('Y-m-d H:i:s');

            $this->addUser($id, $name);

            $lastUser = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastUser['count'] == 5) {
            $id = (string)(intval($lastUser['id']) + 1);
            $name = date('Y-m-d H:i:s');

            $this->addUser($id, $name);

            $lastUser = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastUser == null) {
            return;
        }

        $this->post('/ISAPI/AccessControl/CardInfo/Record?format=json', ['CardInfo' => ['employeeNo' => $lastUser['id'], 'cardNo' => sprintf("%'.010d", hexdec($key->key)), 'cardType' => 'normalCard']]);
    }

    public function removeKey(Key|string $key): void
    {
        $this->put('/ISAPI/AccessControl/CardInfo/Delete?format=json', ['CardInfoDelCond' => ['CardNoList' => [['cardNo' => sprintf("%'.010d", hexdec($key instanceof Key ? $key->key : $key))]]]]);
    }

    public function clearKey(): void
    {
        foreach ($this->getUsers() as $user) {
            $this->put('/ISAPI/AccessControl/UserInfo/Delete?format=json', ['UserInfoDelCond' => ['EmployeeNoList' => [['employeeNo' => (string)$user]]]]);
        }
    }

    public function handleKey(array $flats, HouseEntrance $entrance): void
    {
        $flats = container(DatabaseService::class)->get('SELECT house_flat_id FROM houses_entrances_flats WHERE house_entrance_id = :id', ['id' => $entrance->house_entrance_id]);
        $keys = HouseKey::fetchAll(criteria()->equal('access_type', 2)->in('access_to', array_map(static fn(array $flat) => $flat['house_flat_id'], $flats)));

        /** @var array<string, Key> $rfidKeys */
        $rfidKeys = array_reduce($this->getKeys(null), static function (array $previous, Key $current) {
            $previous[$current->key] = $current;

            return $previous;
        }, []);

        $lastUser = $this->getLastUser();

        if ($lastUser == null) {
            $id = '1';

            $this->addUser($id, date('Y-m-d H:i:s'));

            $lastUser = ['id' => $id, 'count' => 0];
        }

        foreach ($keys as $key) {
            if (array_key_exists($key->rfid, $rfidKeys)) {
                unset($rfidKeys[$key->rfid]);

                continue;
            }

            if ($lastUser['count'] == 5) {
                $id = (string)(intval($lastUser['id']) + 1);

                $this->addUser($id, date('Y-m-d H:i:s'));

                $lastUser = ['id' => $id, 'count' => 0];
            }

            $this->post('/ISAPI/AccessControl/CardInfo/Record?format=json', ['CardInfo' => ['employeeNo' => $lastUser['id'], 'cardNo' => sprintf("%'.010d", hexdec($key->rfid)), 'cardType' => 'normalCard']]);

            $lastUser['count']++;
        }

        foreach ($rfidKeys as $key) {
            $this->removeKey($key);
        }
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

    private function getLastUser(): ?array
    {
        $count = $this->getUsersCount();

        if ($count === 0) {
            return null;
        }

        try {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => strval($count), 'maxResults' => 1, 'searchResultPosition' => $count - 1]]);

            if (!array_key_exists('responseStatusStrg', $response['UserInfoSearch']) || $response['UserInfoSearch']['responseStatusStrg'] !== 'OK') {
                return null;
            }

            return [
                'id' => $response['UserInfoSearch']['UserInfo'][0]['employeeNo'],
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
    private function getUsers(): array
    {
        $result = [];
        $pages = $this->getUsersCount() / 30 + 1;

        for ($i = 1; $i <= $pages; ++$i) {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => '1', 'maxResults' => 30, 'searchResultPosition' => ($i - 1) * 30]]);

            $userInfos = $response['UserInfoSearch']['UserInfo'] ?? [];

            foreach ($userInfos as $userInfo) {
                $result[] = intval($userInfo['employeeNo']);
            }
        }

        return $result;
    }

    private function getUsersCount(): int
    {
        $response = $this->get('/ISAPI/AccessControl/UserInfo/Count');

        return $response['UserInfoCount']['userNumber'] ?? 0;
    }
}