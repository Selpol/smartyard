<?php declare(strict_types=1);

namespace Selpol\Feature\External\Internal;

use Selpol\Feature\External\ExternalFeature;
use Throwable;

readonly class InternalExternalFeature extends ExternalFeature
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

    public function qr(string $fias, int $flat, int $mobile, string $fio, string $note): bool|string
    {
        try {
            return $this->request(['fias' => $fias, 'flat' => $flat, 'mobile' => $mobile, 'fio' => $fio, 'note' => $note], '/api/v1/external/qr');
        } catch (Throwable) {
            return 'Не удалось привязать пользователя к квартире';
        }
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

        $response = curl_exec($request);

        curl_close($request);

        file_logger('external')->debug($response, ['endpoint' => $endpoint, 'data' => $data]);

        try {
            $parse = json_decode($response, true);

            if (array_key_exists('success', $parse)) {
                if ($parse['success'])
                    return array_key_exists('data', $parse) ? json_encode($parse['data']) : true;

                return array_key_exists('message', $parse) ? $parse['message'] : 'Неизвестная ошибка';
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }
}