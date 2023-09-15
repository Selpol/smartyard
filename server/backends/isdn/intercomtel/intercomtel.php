<?php

namespace backends\isdn;

use Psr\Log\LoggerInterface;

class intercomtel extends isdn
{
    private LoggerInterface $logger;

    public function __construct($config, $db, $redis, $login = false)
    {
        parent::__construct($config, $db, $redis, $login);

        $this->logger = logger('isdn');
    }

    public function push(array $push): bool|string
    {
        return $this->request($push, '/api/v1/external/notification');
    }

    public function message(array $push): bool|string
    {
        return $this->request($push, '/api/v1/external/message');

    }

    private function request($push, $endpoint): bool|string
    {
        $idsn = $this->config['backends']['isdn'];

        $request = curl_init($idsn['endpoint'] . $endpoint);

        curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($request, CURLOPT_USERPWD, $idsn['secret']);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($push, JSON_UNESCAPED_UNICODE));
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($request);

        curl_close($request);

        $body = json_decode($response, true);

        $this->logger->debug('Send notification via Intercomtel ' . $idsn['endpoint'] . $endpoint, $body);

        return false;
    }
}
