<?php declare(strict_types=1);

namespace Selpol\Service;

use Psr\Http\Message\UriInterface;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton]
readonly class SmsService
{
    private Client $client;

    private UriInterface $uri;

    public function __construct()
    {
        $this->client = container(Client::class);

        $this->uri = uri(config_get('sms.uri'));
    }

    public function send(string $to, string $message): bool
    {
        $uri = clone $this->uri;
        $uri = $uri->withQuery('phoneNumber=' . $to . '&message=' . $message);

        $request = client_request('GET', $uri);
    
        $response = $this->client->send($request);

        return $response->getStatusCode() == 200;
    }
}
