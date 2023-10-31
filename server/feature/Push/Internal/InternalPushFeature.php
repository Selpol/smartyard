<?php declare(strict_types=1);

namespace Selpol\Feature\Push\Internal;

use Selpol\Feature\Push\PushFeature;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Http\Stream;

readonly class InternalPushFeature extends PushFeature
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

        $request = http()->createRequest('POST', $push['endpoint'] . $endpoint);

        $request->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::memory(json_encode($data, JSON_UNESCAPED_UNICODE)));

        $response = container(Client::class)->send($request, (new ClientOption())->raw(CURLOPT_USERPWD, $push['secret']));
        $content = $response->getBody()->getContents();

        file_logger('notification')->debug($content, ['endpoint' => $endpoint, 'data' => $data]);

        return $content;
    }
}