<?php declare(strict_types=1);

namespace Selpol\Service;

use MongoDB\Client;
use MongoDB\Database;
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

    public function getDatabase(string $database): Database
    {
        return $this->getClient()->{$database};
    }
}