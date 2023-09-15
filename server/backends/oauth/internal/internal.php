<?php

namespace backends\oauth {

    use Selpol\Service\ClientService;
    use Throwable;

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

                try {
                    $response = container(ClientService::class)->post($webApi . $endpoint, $data, ['Content-Type' => 'application/json', 'Authorization' => 'Basic ' . base64_encode($secret)]);

                    $body = $response->getParsedBody();

                    logger('intercomtel')->debug('Send register to: ' . $webApi . $endpoint, ['response' => $body]);

                    return array_key_exists('success', $body) && $body['success'] ? $body['data'] : null;
                } catch (Throwable $throwable) {
                    logger('intercomtel')->error($throwable);

                    return null;
                }
            }

            return null;
        }
    }
}
