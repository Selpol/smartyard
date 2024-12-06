<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton]
readonly class ZabbixService
{
    private Client $client;

    private ?string $endpoint;
    private ?string $key;

    public function __construct()
    {
        $config = config('zabbix');

        $this->client = container(Client::class);

        $this->endpoint = $config['endpoint'];
        $this->key = $config['key'];
    }

    public function addIntercom(DeviceIntercom $intercom): void
    {
        if ($this->endpoint == null || $this->key == null) {
            return;
        }

        $device = container(DeviceService::class)->intercomByEntity($intercom);

        $info = $device->getSysInfo();

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'host.create',
            'params' => [
                'host' => $intercom->ip,
                'interfaces' => [['type' => 1, 'main' => 1, 'useip' => 1, 'ip' => $intercom->ip, 'port' => '10050']],
                'groups' => [
                    array_map(
                        static fn(string $value) => ['groupid' => $value],
                        array_map('trim', explode(',', $device->resolver->string('zabbix.group', '1')))
                    )
                ],
                'templates' => [
                    array_map(
                        static fn(string $value) => ['templateid' => $value],
                        array_map('trim', explode(',', $device->resolver->string('zabbix.template', '1')))
                    )
                ],
                'macros' => [
                    ['macro' => '{$DEVICE_LOGIN}', 'value' => $device->login],
                    ['macro' => '{$DEVICE_PASSWORD}', 'value' => $device->password]
                ],
                'inventory_mode' => 1,
                'inventory' => [
                    'type' => $device->model->vendor,
                    'serialno_a' => $info->deviceId,
                    'model' => $info->deviceModel,
                    'hardware' => $info->hardwareVersion,
                    'software' => $info->softwareVersion,
                    'url_a' => 'http://' . $intercom->ip,
                    'site_address_a' => $intercom->comment
                ]
            ],
            'auth' => $this->key,
            'id' => 1
        ];

        if ($intercom->comment) {
            $data['params']['name'] = $intercom->comment;
        }

        if ($info->mac) {
            $data['params']['inventory']['macaddress_a'] = $info->mac;
        }

        $this->client->send(
            request('POST', $this->endpoint)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(stream($data))
        );
    }
}
