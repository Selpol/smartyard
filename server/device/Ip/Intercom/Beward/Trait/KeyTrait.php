<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Key\Key;
use Selpol\Feature\Config\ConfigKey;

trait KeyTrait
{
    /**
     * @param int|null $apartment
     * @return Key[]
     * @return Key[]
     */
    public function getKeys(?int $apartment): array
    {
        if ($this->mifare) {
            $response = $this->get('/cgi-bin/' . $this->resolver->string(ConfigKey::MifareCgi, 'mifareusr_cgi'), ['action' => 'list'], parse: ['type' => 'param']);
        } else {
            $response = $this->get('/cgi-bin/rfid_cgi', ['action' => 'list'], parse: ['type' => 'param']);
        }

        if (!is_array($response) || count($response) == 0) {
            return [];
        }

        $end = intval(substr((string)array_key_last($response), 5));

        $result = [];

        for ($i = 1; $i <= $end; ++$i) {
            if (!array_key_exists('Key' . $i, $response)) {
                continue;
            }

            $key = $response['Key' . $i];

            if (!$key) {
                continue;
            }

            $result[] = new Key($key, intval($response['Apartment' . $i]));
        }

        usort($result, static fn(Key $a, Key $b): int => strcmp($a->key, $b->key));

        return $result;
    }

    public function addKey(Key $key): void
    {
        if ($this->mifare) {
            $cgi = $this->resolver->string(ConfigKey::MifareCgi, 'mifareusr_cgi');

            if ($cgi === 'mifareusr_cgi') {
                $this->get('/cgi-bin/' . $cgi, ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment, 'CipherIndex' => 1]);
            } else {
                $this->get('/cgi-bin/' . $cgi, ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment, 'Type' => 1, 'ProtectedMode' => 'on', 'CipherIndex' => 1, 'Sector' => 3]);
            }
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $key->key, 'Apartment' => $key->apartment]);
        }
    }

    public function removeKey(Key|string $key): void
    {
        if ($key instanceof Key) {
            if ($this->mifare) {
                $this->get('/cgi-bin/' . $this->resolver->string(ConfigKey::MifareCgi, 'mifareusr_cgi'), ['action' => 'delete', 'Key' => $key->key, 'Apartment' => $key->apartment]);
            } else {
                $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $key->key, 'Apartment' => $key->apartment]);
            }
        } elseif ($this->mifare) {
            $this->get('/cgi-bin/' . $this->resolver->string(ConfigKey::MifareCgi), ['action' => 'delete', 'Key' => $key]);
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $key]);
        }
    }

    public function clearKey(): void
    {
        if ($this->mifare) {
            $this->get('/cgi-bin/' . $this->resolver->string(ConfigKey::MifareCgi, 'mifareusr_cgi'), ['action' => 'clear']);
        } else {
            $this->get('/cgi-bin/rfid_cgi', ['action' => 'clear']);
        }
    }
}