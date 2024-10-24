<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Throwable;

trait KeyTrait
{
    public function getKeys(?int $apartment): array
    {
        try {
            $response = $this->get('/key/store', is_null($apartment) ? [] : ['panelCode' => $apartment]);
            $result = array_map(static fn(array $key): Key => new Key($key['uuid'], $key['panelCode']), $response);

            usort($result, static fn(Key $a, Key $b): int => strcmp($a->key, $b->key));

            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    public function addKey(Key $key): void
    {
        $this->post('/key/store', ['uuid' => $key->key, 'panelCode' => $key->apartment, 'encryption' => true]);
    }

    public function removeKey(Key|string $key): void
    {
        if ($key instanceof Key) {
            $this->delete('/key/store/' . $key->key);
        } else {
            $this->delete('/key/store/' . $key);
        }
    }

    public function clearKey(): void
    {
        $this->delete('/key/store/clear');
    }
}