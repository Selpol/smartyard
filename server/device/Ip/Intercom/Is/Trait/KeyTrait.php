<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;

trait KeyTrait
{
    public function getKeys(int $apartment): array
    {
        $response = $this->get('/key/store', ['panelCode' => $apartment]);

        return array_map(static fn(array $key) => new Key($key['uuid'], $key['panelCode']), $response);
    }

    public function addKey(Key $key): void
    {
        $this->post('/key/store', ['uuid' => $key->key, 'panelCode' => $key->apartment, 'encryption' => true]);
    }

    public function removeKey(Key|string $key): void
    {
        if ($key instanceof Key)
            $this->delete('/key/store/' . $key->key);
        else
            $this->delete('/key/store/' . $key);
    }

    public function clearKey(): void
    {
        $this->delete('/key/store/clear');
    }
}