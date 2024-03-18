<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

class MifareDksIntercom extends DksIntercom
{
    public function addRfid(string $code, int $apartment): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'add', 'Key' => $code, 'Apartment' => $apartment, 'Type' => 1, 'ProtectedMode' => 'on', 'CipherIndex' => 1, 'Sector' => 3]);
    }

    public function removeRfid(string $code, int $apartment): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $code, 'Apartment' => $apartment]);
    }

    public function clearRfid(): void
    {
        if ($this->model->mifare)
            $this->get('/cgi-bin/mifare_cgi', ['action' => 'clear']);

        foreach ($this->getRfids() as $rfid)
            $this->removeRfid($rfid, 0);
    }
}