<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;

trait ApartmentTrait
{
    /**
     * @return Apartment[]
     */
    public function getApartments(): array
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'list'], parse: false));

        $start = intval(substr($response[array_key_first($response)], 6));
        $end = intval(substr($response[array_key_last($response)], 5, -2));

        $result = [];

        for ($i = $start; $i <= $end; $i++) {
            if (!array_key_exists('Number' . $i, $response))
                continue;

            $number = $response['Number' . $i];

            if (!$number)
                continue;

            $numbers = array_map(
                static fn(string $value) => $response[$value],
                ['Phone' . $i . '_1', 'Phone' . $i . '_2', 'Phone' . $i . '_3', 'Phone' . $i . '_4', 'Phone' . $i . '_5']
            );

            $result[] = new Apartment(
                intval($number),
                $response['BlockCMS' . $i] === 'off',
                $response['PhonesActive' . $i] === 'on',
                intval($response['HandsetUpLevel' . $i]),
                intval($response['DoorOpenLevel' . $i]),
                array_filter($numbers, static fn(string $value) => $value !== '' && $value !== $number)
            );
        }

        return $result;
    }

    public function addApartment(Apartment $apartment): void
    {
        $this->setApartment($apartment);
    }

    public function setApartment(Apartment $apartment): void
    {
        $params = [
            'action' => 'set',
            'Number' => $apartment->apartment,
            'RegCodeActive' => 'off',
            'BlockCMS' => $apartment->handset ? 'off' : 'on',
            'PhonesActive' => $apartment->sip ? 'on' : 'off',
        ];

        $params['HandsetUpLevel'] = $apartment->answer;
        $params['DoorOpenLevel'] = $apartment->quiescent;

        if (count($apartment->numbers)) {
            $sipNumbers = array_merge([$apartment], $apartment->numbers);

            for ($i = 1; $i <= count($sipNumbers); $i++)
                $params['Phone' . $i] = $sipNumbers[$i - 1];
        }

        $this->get('/cgi-bin/apartment_cgi', $params);
    }

    public function removeApartment(Apartment|int $apartment): void
    {
        if ($apartment instanceof Apartment)
            $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => $apartment->apartment]);
        else
            $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => $apartment]);
    }

    public function clearApartments(): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => 1, 'LastNumber' => 9999]);
    }
}