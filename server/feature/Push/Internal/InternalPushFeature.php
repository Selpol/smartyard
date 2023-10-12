<?php

namespace Selpol\Feature\Push\Internal;

use Selpol\Feature\Push\PushFeature;

class InternalPushFeature extends PushFeature
{
    public function push(array $push): bool|string
    {
        return $this->request($push, '/api/v1/external/notification');
    }

    public function message(array $push): bool|string
    {
        return $this->request($push, '/api/v1/external/message');

    }

    public function logout(array $push): bool|string
    {
        return false;
    }

    private function request($data, $endpoint): bool|string
    {
        $push = config_get('feature.push');

        $request = curl_init($push['endpoint'] . $endpoint);

        curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($request, CURLOPT_USERPWD, $push['secret']);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($request);
        curl_close($request);

        return false;
    }
}