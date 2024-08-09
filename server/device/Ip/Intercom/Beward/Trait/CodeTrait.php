<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;

trait CodeTrait
{
    /**
     * @param int $apartment
     * @return Code[]
     */
    public function getCodes(int $apartment): array
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'get', 'Number' => $apartment], parse: false));

        if ($response['DoorCodeActive'] === 'on' && $response['DoorCode'] !== '0')
            return [new Code(intval($response['DoorCode']), $apartment)];

        return [];
    }

    public function addCode(Code $code): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'Number' => $code->apartment, 'DoorCodeActive' => 'on', 'DoorCode' => $code->code]);
    }

    public function removeCode(Code $code): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'Number' => $code->apartment, 'DoorCodeActive' => 'off', 'DoorCode' => '0']);
    }

    public function clearCode(): void
    {
        $apartments = $this->getApartments();

        foreach ($apartments as $apartment)
            $this->removeCode(new Code(0, $apartment->apartment));
    }
}