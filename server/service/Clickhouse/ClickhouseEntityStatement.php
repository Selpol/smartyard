<?php declare(strict_types=1);

namespace Selpol\Service\Clickhouse;

use Psr\Http\Message\RequestInterface;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Framework\Entity\EntityMessage;
use Selpol\Framework\Entity\Exception\EntityException;
use Throwable;

class ClickhouseEntityStatement implements EntityStatementInterface
{
    private array $data = [];

    private array $error = [];

    public function __construct(private readonly ClientOption $option, private readonly RequestInterface $request, private readonly string $value)
    {
    }

    public function execute(?array $value = null): bool
    {
        $query = $this->value;

        if ($value !== null && $value !== []) {
            foreach ($value as $key => $item) {
                if (is_string($item)) {
                    $item = "'" . str_replace("'", "\'", $item) . "'";
                }

                $query = str_replace(':' . $key, (string)$item, $query);
            }
        }

        try {
            $this->request->withBody(stream($query));

            $response = container(Client::class)->send($this->request, $this->option);

            if ($response->getStatusCode() === 200) {
                if ($response->getHeaderLine('X-ClickHouse-Format') == 'JSON') {
                    $body = json_decode($response->getBody()->getContents(), true);

                    if (is_array($body) && array_key_exists('data', $body) && is_array($body['data'])) {
                        $this->data = $body['data'];
                    }
                }

                return true;
            }

            $code = $response->getHeaderLine('X-ClickHouse-Exception-Code');

            if ($code) {
                $message = $response->getBody()->getContents();
                $error = 'Code: ' . $code . '. ';

                if (str_starts_with($message, $error)) {
                    $message = substr($message, strlen($error));
                }

                $this->error[] = new EntityMessage(intval($code), $message);
            }

            return false;
        } catch (Throwable $throwable) {
            throw new EntityException($this->error, throwable: $throwable);
        }
    }

    public function fetch(int $flags = self::FETCH_ASSOC): ?array
    {
        return $this->data !== [] ? $this->data[0] : null;
    }

    public function fetchColumn(int $index): mixed
    {
        return $this->data !== [] ? $this->data[0][$index] : null;
    }

    public function fetchAll(int $flags = self::FETCH_ASSOC): array
    {
        return $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function error(): array
    {
        return $this->error;
    }
}