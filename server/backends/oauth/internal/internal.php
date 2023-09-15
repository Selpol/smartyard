<?php

namespace backends\oauth {
    class internal extends oauth
    {
        public function register(string $mobile): ?string
        {
            return $this->request('/api/v1/external/register', ['mobile' => $mobile]);
        }

        private function request(string $endpoint, mixed $data): ?string
        {
            $oauth = config('backends.oauth');

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

                logger('intercomtel')->debug('Send register to: ' . $webApi . $endpoint, $body);

                if ($body['success'])
                    return $body['data'];
            }

            return null;
        }
    }
}