<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

readonly class IntercomOption
{
    public bool $mifare;

    public function __construct(
        bool                $mifare,
        public int          $primaryBitrate,
        public int          $secondaryBitrate,
        public IntercomAuth $auth,
    )
    {
        $this->mifare = $mifare && env('MIFARE_SECTOR', 0) > 0;
    }

    public function toArray(): array
    {
        return [
            'mifare' => $this->mifare,

            'primaryBitrate' => $this->primaryBitrate,
            'secondaryBitrate' => $this->secondaryBitrate,

            'auth' => $this->auth
        ];
    }
}