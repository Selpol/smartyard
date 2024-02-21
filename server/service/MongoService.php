<?php declare(strict_types=1);

namespace Selpol\Service;

use MongoDB\Client;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton]
readonly class MongoService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(config_get('mongo.uri'));
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}