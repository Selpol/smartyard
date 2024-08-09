<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use DateInterval;
use DateTime;
use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Throwable;

trait KeyTrait
{

    /**
     * @param int $apartment
     * @return Key[]
     * @return Key[]
     */
    public function getKeys(int $apartment): array
    {
        return [];
    }

    public function addKey(Key $key): void
    {
        $lastUser = $this->getLastUser();

        if ($lastUser == null) {
            $id = '1';
            $name = (string)$key->apartment;

            $this->addUser($id, $name);

            $lastUser = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastUser['count'] == 5) {
            $id = (string)(intval($lastUser['id']) + 1);
            $name = (string)$key->apartment;

            $this->addUser($id, $name);

            $lastUser = ['id' => $id, 'name' => $name, 'count' => 0];
        }

        if ($lastUser == null)
            return;

        $this->post('/ISAPI/AccessControl/CardInfo/Record?format=json', ['CardInfo' => ['employeeNo' => $lastUser['id'], 'cardNo' => sprintf("%'.010d", hexdec($key->key)), 'cardType' => 'normalCard']]);
    }

    public function removeKey(Key|string $key): void
    {
        $this->put('/ISAPI/AccessControl/CardInfo/Delete?format=json', ['CardInfoDelCond' => ['CardNoList' => [['cardNo' => sprintf("%'.010d", hexdec($key instanceof Key ? $key->key : $key))]]]]);
    }

    public function clearKey(): void
    {
        foreach ($this->getUsers() as $user)
            $this->put('/ISAPI/AccessControl/UserInfo/Delete?format=json', ['UserInfoDelCond' => ['EmployeeNoList' => [['employeeNo' => (string)$user]]]]);
    }

    public function handleKey(array $flats): void
    {
        $this->clearKey();
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

    private function getLastUser(): ?array
    {
        $count = $this->getUsersCount();

        if ($count === 0)
            return null;

        try {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => strval($count), 'maxResults' => 1, 'searchResultPosition' => $count - 1]]);

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
    private function getUsers(): array
    {
        $result = [];
        $pages = $this->getUsersCount() / 20 + 1;

        for ($i = 1; $i <= $pages; $i++) {
            $response = $this->post('/ISAPI/AccessControl/UserInfo/Search?format=json', ['UserInfoSearchCond' => ['searchID' => '1', 'maxResults' => 20, 'searchResultPosition' => ($i - 1) * 20]]);

            $userInfos = $response['UserInfoSearch']['UserInfo'] ?? [];

            foreach ($userInfos as $userInfo)
                $result[] = intval($userInfo['employeeNo']);
        }

        return $result;
    }

    private function getUsersCount(): int
    {
        $response = $this->get('/ISAPI/AccessControl/UserInfo/Count');

        return $response['UserInfoCount']['userNumber'] ?? 0;
    }
}