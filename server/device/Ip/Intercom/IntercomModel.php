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
        public readonly string $model,
        public readonly int    $outputs,
        /** @var string[] */
        public readonly array  $cmses,
        /**@var array<string, int|string> */
        public readonly array  $cmsesMap,
        public readonly string $class
    )
    {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,
            'model' => $this->model,

            'outputs' => $this->outputs,

            'cmses' => $this->cmses,

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
        return $this->vendor == 'HIKVISION' || $this->title == 'HikVision DS-KV6113';
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
                    'ISCOM X1',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VISIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    IsIntercom::class
                ),
                'iscomx1_2' => new IntercomModel(
                    'IS ISCOM X1 2.2.5.10.5',
                    'IS',
                    'ISCOM X1 2.2.5.10.5',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VIZIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    IsIntercom::class
                ),
                'iscomx5' => new IntercomModel(
                    'IS ISCOM X5',
                    'IS',
                    'ISCOM X5',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VIZIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    Is5Intercom::class
                ),
                'iscomx5_rfid' => new IntercomModel(
                    'IS ISCOM X5 RFID',
                    'IS',
                    'ISCOM X5 RFID',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VIZIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    Is5Intercom::class
                ),
                'dks15102' => new IntercomModel(
                    'BEWARD DKS15102',
                    'BEWARD',
                    'DKS15102',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    DksIntercom::class
                ),
                'dks15103' => new IntercomModel(
                    'BEWARD DKS15103',
                    'BEWARD',
                    'DKS15103',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    DksIntercom::class
                ),
                'dsk15103_52701' => new IntercomModel(
                    'BEWARD DKS15103_rev5.2.7.0.1',
                    'BEWARD',
                    'DKS15103_rev5.2.7.0.1',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    DksIntercom::class
                ),
                'dks15104' => new IntercomModel(
                    'BEWARD DKS15104',
                    'BEWARD',
                    'DKS15104',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105', 'kkm-108'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2, 'KKM-108' => 19],
                    DksIntercom::class
                ),
                'dks15105' => new IntercomModel(
                    'BEWARD DKS15105',
                    'BEWARD',
                    'DKS15105',
                    3,
                    ['kad2501', 'kad2502', 'kkm-100s2', 'kkm-105', 'kkm-108'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KKM-108' => 3, 'KAD2501' => 2, 'KAD2502' => 4],
                    DksIntercom::class
                ),
                'dks15122' => new IntercomModel(
                    'BEWARD DKS15122',
                    'BEWARD',
                    'DKS15122',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    DksIntercom::class
                ),
                'dks15374' => new IntercomModel(
                    'BEWARD DKS15374',
                    'BEWARD',
                    'DKS15374',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    DksIntercom::class
                ),
                'dks15374_rev5.2.8.2.1' => new IntercomModel(
                    'BEWARD DKS15374_rev5.2.8.2.1',
                    'BEWARD',
                    'DKS15374_rev5.2.8.2.1',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    DksIntercom::class
                ),
                'dks15374_rfid' => new IntercomModel(
                    'BEWARD DKS15374 RFID',
                    'BEWARD',
                    'DKS15374 RFID',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    DksIntercom::class
                ),
                'dks15374_is10' => new IntercomModel(
                    'BEWARD DKS15374 IS10',
                    'BEWARD',
                    'DKS15374 IS10',
                    2,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    DksIntercom::class
                ),
                'dks20210' => new IntercomModel(
                    'BEWARD DKS20210',
                    'BEWARD',
                    'DKS20210',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    DksIntercom::class
                ),
                'dks977957' => new IntercomModel(
                    'BEWARD DKS977957',
                    'BEWARD',
                    'DKS977957',
                    2,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    MifareDksIntercom::class
                ),
                'kv6113' => new IntercomModel(
                    'HikVision DS-KV6113',
                    'HIKVISION',
                    'DS-KV6113',
                    1,
                    [],
                    [],
                    HikVisionIntercom::class
                ),
                'ds06ap' => new IntercomModel(
                    'BEWARD DS06A(P)',
                    'BEWARD',
                    'DS06A(P)',
                    3,
                    [],
                    [],
                    DsIntercom::class
                ),
                'ds06m' => new IntercomModel(
                    'BEWARD DS06M',
                    'BEWARD',
                    'DS06M',
                    3,
                    [],
                    [],
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