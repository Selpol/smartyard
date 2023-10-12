<?php

namespace Selpol\Feature\Oauth\Internal;

use Selpol\Feature\Oauth\OauthFeature;

class InternalOauthFeature extends OauthFeature
{
    public function validateJwt(string $value): ?array
    {
        $oauth = config_get('feature.oauth');

        list($header, $payload, $signature) = explode('.', $value);
        $decoded_signature = base64_decode(str_replace(array('-', '_'), array('+', '/'), $signature));

        $publicKey = file_get_contents($oauth['public_key']);

        if (openssl_verify(utf8_decode($header . '.' . $payload), $decoded_signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1)
            return null;

        $jwt = json_decode(base64_decode($payload), true);

        if (time() <= $jwt['nbf'] || time() >= $jwt['exp'])
            return null;

        $audience = explode(',', $oauth['audience']);

        if (!in_array($jwt['aud'], $audience) || !array_key_exists('scopes', $jwt) || !array_key_exists(1, $jwt['scopes']))
            return null;

        return $jwt;
    }

    public function register(string $mobile): ?string
    {
        return $this->request('/api/v1/external/register', ['mobile' => $mobile]);
    }

    private function request(string $endpoint, mixed $data): ?string
    {
        $oauth = config_get('feature.oauth');

        if (array_key_exists('web_api', $oauth) && array_key_exists('secret', $oauth)) {
            $webApi = $oauth['web_api'];
            $secret = $oauth['secret'];

            $request = curl_init($webApi . $endpoint);

            curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($request, CURLOPT_USERPWD, $secret);
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_POST, 1);

            $response = curl_exec($request);
            $body = json_decode($response, true);

            file_logger('intercomtel')->debug('Send register to: ' . $webApi . $endpoint, $body);

            if ($body['success'])
                return $body['data'];
        }

        return null;
    }
}