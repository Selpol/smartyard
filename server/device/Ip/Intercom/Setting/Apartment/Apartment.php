<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Apartment;

class Apartment
{
    public int $apartment;

    public bool $handset;
    public bool $sip;

    public ?int $answer;
    public ?int $quiescent;

    public array $numbers;

    public function __construct(int $apartment, bool $handset, bool $sip, ?int $answer, ?int $quiescent, array $numbers)
    {
        $this->apartment = $apartment;

        $this->handset = $handset;
        $this->sip = $sip;

        $this->answer = $answer;
        $this->quiescent = $quiescent;

        $this->numbers = $numbers;
    }

    public function equal(Apartment $other): bool
    {
        return $this->equalWithoutNumbers($other) && $this->numbers == $other->numbers;
    }

    public function equalWithoutNumbers(Apartment $other): bool
    {
        return $this->apartment === $other->apartment && $this->handset === $other->handset && $this->sip === $other->sip && $this->answer === $other->answer && $this->quiescent === $other->quiescent;
    }
}