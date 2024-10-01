<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\Intercom\Beward\DksIntercom;
use Selpol\Device\Ip\Intercom\Beward\DsIntercom;
use Selpol\Device\Ip\Intercom\Beward\MifareDksIntercom;
use Selpol\Device\Ip\Intercom\HikVision\HikVisionIntercom;
use Selpol\Device\Ip\Intercom\Is\Is5Intercom;
use Selpol\Device\Ip\Intercom\Is\IsIntercom;

class IntercomModel
{
    /**
     * @var IntercomModel[]
     */
    private static array $models;

    public function __construct(
        public readonly string $title,
        public readonly string $vendor,
        public readonly int    $outputs,
        public readonly string $class
    )
    {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,

            'outputs' => $this->outputs,

            'class' => $this->class
        ];
    }

    public function isIs(): bool
    {
        return $this->vendor == 'IS';
    }

    public function isBeward(): bool
    {
        return $this->vendor == 'BEWARD';
    }

    public function isHikVision(): bool
    {
        return $this->vendor == 'HIKVISION';
    }

    public static function modelsToArray(): array
    {
        return array_map(static fn(IntercomModel $model): array => $model->toArray(), self::models());
    }

    /**
     * @return IntercomModel[]
     */
    public static function models(): array
    {
        if (!isset(self::$models)) {
            self::$models = [
                'iscomx1' => new IntercomModel(
                    'IS ISCOM X1',
                    'IS',
                    1,
                    IsIntercom::class
                ),
                'iscomx1_2' => new IntercomModel(
                    'IS ISCOM X1 2.2.5.10.5',
                    'IS',
                    1,
                    IsIntercom::class
                ),
                'iscomx5' => new IntercomModel(
                    'IS ISCOM X5',
                    'IS',
                    1,
                    Is5Intercom::class
                ),
                'iscomx5_rfid' => new IntercomModel(
                    'IS ISCOM X5 RFID',
                    'IS',
                    1,
                    Is5Intercom::class
                ),
                'dks15102' => new IntercomModel(
                    'BEWARD DKS15102',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dks15103' => new IntercomModel(
                    'BEWARD DKS15103',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dsk15103_52701' => new IntercomModel(
                    'BEWARD DKS15103_rev5.2.7.0.1',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dks15104' => new IntercomModel(
                    'BEWARD DKS15104',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dks15105' => new IntercomModel(
                    'BEWARD DKS15105',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dks15122' => new IntercomModel(
                    'BEWARD DKS15122',
                    'BEWARD',
                    3,
                    DksIntercom::class
                ),
                'dks15374' => new IntercomModel(
                    'BEWARD DKS15374',
                    'BEWARD',
                    1,
                    DksIntercom::class
                ),
                'dks15374_rev5.2.8.2.1' => new IntercomModel(
                    'BEWARD DKS15374_rev5.2.8.2.1',
                    'BEWARD',
                    1,
                    DksIntercom::class
                ),
                'dks15374_rfid' => new IntercomModel(
                    'BEWARD DKS15374 RFID',
                    'BEWARD',
                    1,
                    DksIntercom::class
                ),
                'dks15374_is10' => new IntercomModel(
                    'BEWARD DKS15374 IS10',
                    'BEWARD',
                    2,
                    DksIntercom::class
                ),
                'dks20210' => new IntercomModel(
                    'BEWARD DKS20210',
                    'BEWARD',
                    1,
                    DksIntercom::class
                ),
                'dks977957' => new IntercomModel(
                    'BEWARD DKS977957',
                    'BEWARD',
                    2,
                    MifareDksIntercom::class
                ),
                'kv6113' => new IntercomModel(
                    'HikVision DS-KV6113',
                    'HIKVISION',
                    1,
                    HikVisionIntercom::class
                ),
                'ds06ap' => new IntercomModel(
                    'BEWARD DS06A(P)',
                    'BEWARD_DS',
                    3,
                    DsIntercom::class
                ),
                'ds06m' => new IntercomModel(
                    'BEWARD DS06M',
                    'BEWARD_DS',
                    3,
                    DsIntercom::class
                )
            ];
        }

        return self::$models;
    }

    public static function model(string $value): ?IntercomModel
    {
        if (array_key_exists($value, self::models())) {
            return self::$models[$value];
        }

        return null;
    }
}