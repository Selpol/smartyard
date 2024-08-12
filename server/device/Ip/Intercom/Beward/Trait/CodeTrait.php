<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;

trait CodeTrait
{
    public function getCodes(?int $apartment): array
    {
        if (!is_null($apartment)) {
            $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'get', 'Number' => $apartment], parse: false));

            if ($response['DoorCodeActive'] === 'on' && $response['DoorCode'] !== '0')
                return [new Code(intval($response['DoorCode']), $apartment)];

            return [];
        }

        $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'list'], parse: false));

        $start = intval(substr(array_key_first($response), 6));
        $end = intval(substr(array_key_last($response), 5, -2));

        $result = [];

        for ($i = $start; $i <= $end; $i++) {
            if (!array_key_exists('Number' . $i, $response)) {
                continue;
            }

            $number = intval($response['Number' . $i]);

            if ($response['DoorCodeActive' . $i] === 'on' && $response['DoorCode' . $i] !== '0') {
                $result[] = new Code(intval($response['DoorCode' . $i]), $number);
            }
        }

        return $result;
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