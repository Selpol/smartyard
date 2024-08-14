<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;

trait KeyTrait
{
    /**
     * @param int|null $apartment
     * @return Key[]
     * @return Key[]
     */
    public function getKeys(?int $apartment): array
    {
        if ($this->model->mifare) {
            $response = $this->parseParamValueHelp($this->get('/cgi-bin/mifareusr_cgi', ['action' => 'list'], parse: false));
        } else {
            $response = $this->parseParamValueHelp($this->get('/cgi-bin/rfid_cgi', ['action' => 'list'], parse: false));
        }

        if (count($response) == 0) {
            return [];
        }

        $end = intval(substr(array_key_last($response), 5));

        $result = [];

        for ($i = 1; $i <= $end; $i++) {
            if (!array_key_exists('Key' . $i, $response)) {
                continue;
            }

            $key = $response['Key' . $i];

            if (!$key) {
                continue;
            }

            $result[] = new Key($key, intval($response['Apartment' . $i]));
        }

        usort($result, static fn(Key $a, Key $b) => strcmp($a->key, $b->key));

        return $result;
    }

    public function addKey(Key $key): void
    {
        if ($this->model->mifare) {
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment, 'CipherIndex' => 1]);
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment]);
        }
    }

    public function removeKey(Key|string $key): void
    {
        if ($key instanceof Key) {
            if ($this->model->mifare) {
                $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'delete', 'Key' => $key->key, 'Apartment' => $key->apartment]);
            } else {
                $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $key->key, 'Apartment' => $key->apartment]);
            }
        } else {
            if ($this->model->mifare) {
                $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'delete', 'Key' => $key]);
            } else {
                $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $key]);
            }
        }
    }

    public function clearKey(): void
    {
        if ($this->model->mifare) {
            $this->get('/cgi-bin/mifareusr_cgi', ['action' => 'clear']);
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'clear']);
        }
    }
}