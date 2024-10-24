<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;

class MifareDksIntercom extends DksIntercom
{
    public function addKey(Key $key): void
    {
        if ($this->model->option->mifare) {
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment, 'Type' => 1, 'ProtectedMode' => 'on', 'CipherIndex' => 1, 'Sector' => 3]);
        }
    }

    public function removeKey(Key|string $key): void
    {
        if ($this->model->option->mifare) {
            if ($key instanceof Key) {
                $this->get('/cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $key->key, 'Apartment' => $key->apartment]);
            } else {
                $this->get('/cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $key]);
            }
        }
    }

    public function clearKey(): void
    {
        if ($this->model->option->mifare) {
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'clear']);
        }
    }
}