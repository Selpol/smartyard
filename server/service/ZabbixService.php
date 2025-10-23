<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Framework\Client\Client;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton]
readonly class ZabbixService
{
    private Client $client;

    private ?string $uri;
    private ?string $auth;

    public function __construct()
    {
        $this->client = container(Client::class);

        $this->uri = config_get('zabbix.uri');
        $this->auth = config_get('zabbix.auth');
    }

    public function host_get(string $ip): ?array
    {
        return $this->request('host.get', [
            'output' => ['hostid', 'host', 'name'],
            'selectTags' => 'extend',
            'selectInterfaces' => ['ip'],
            'filter' => [
                'ip' => $ip
            ]
        ]);
    }

    public function trigger_get(array $ids): ?array
    {
        return $this->request('trigger.get', ['output' => ['description', 'lastchange'], 'selectTags' => 'extend', 'hostids' => $ids, 'only_true' => true, 'active' => true, 'skipDependent' => true, 'monitored' => true]);
    }

    private function request(string $method, array $params, int $id = 1): ?array
    {
        $request = client_request('POST', $this->uri)
            ->withHeader('Content-Type', 'application/json-rpc')
            ->withHeader('Authorization', 'Bearer ' . $this->auth)
            ->withBody(stream(json_encode([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => $id
            ])));

        $response = $this->client->send($request);

        if ($response->getStatusCode() == 200) {
            $result = json_decode($response->getBody()->getContents(), true);

            if (array_key_exists('id', $result) && $result['id'] == $id) {
                if (array_key_exists('result', $result)) {
                    return $result['result'];
                } else {
                    file_logger('zabbix')->error('Error zabbix request', $result);
                }
            }
        }

        return null;
    }
}