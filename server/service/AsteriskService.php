<?php declare(strict_types=1);

namespace Selpol\Service;

use PHPAMI\Ami;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;
use Selpol\Service\Asterisk\Contact;

#[Singleton]
class AsteriskService implements ContainerDisposeInterface
{
    private Ami $connection;

    public function __construct()
    {
        $this->connection = new Ami(null, [
            'server' => trim(env('ASTERISK_HOST')),
            'port' => intval(trim(env('ASTERISK_PORT'))),

            'username' => trim(env('ASTERISK_USERNAME')),
            'secret' => trim(env('ASTERISK_PASSWORD')),
        ]);
    }

    public function getConnection(): Ami
    {
        return $this->connection;
    }

    public function command(string $command, ?string $actionId = null): array
    {
        return $this->connection->command($command, $actionId);
    }

    /**
     * @return Contact[]
     */
    public function contacts(): array
    {
        $this->connection->connect();

        $response = $this->command('pjsip show contacts');

        if (!array_key_exists('data', $response)) {
            return [];
        }

        $data = trim($response['data']);
        $lines = explode(PHP_EOL, $data);

        $start = strpos($lines[0], '<');
        $end = strpos($lines[0], '>', $start);

        $result = [];

        for ($i = 3; $i < count($lines) - 2; $i++) {
            $value = trim(substr($lines[$i], $start, $end - $start + 3));
            $contract = $value;

            $position = strpos($contract, '/');

            if ($position) {
                $contract = substr($contract, $position + 1);
            }

            $position = strpos($contract, ';');

            if ($position) {
                $contract = substr($contract, 0, $position);
            }

            if (str_starts_with($contract, 'sip:')) {
                $contract = 'sip://' . substr($contract, 4);
            }

            $uri = uri($contract);

            $result[] = new Contact($value, $uri, $uri->getHost());
        }

        $this->connection->disconnect();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function dispose(): void
    {
        $this->connection->disconnect();
    }
}
