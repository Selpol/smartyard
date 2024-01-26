<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\Intercom\Beward\DksIntercom;
use Selpol\Device\Ip\Intercom\Beward\DsIntercom;
use Selpol\Device\Ip\Intercom\Beward\MifareDksIntercom;
use Selpol\Device\Ip\Intercom\HikVision\HikVisionIntercom;
use Selpol\Device\Ip\Intercom\Is\IsIntercom;

class IntercomModel
{
    /**
     * @var IntercomModel[]
     */
    private static array $models;

    public readonly string $title;
    public readonly string $vendor;
    public readonly string $model;

    public readonly string $syslog;
    public readonly string $camera;

    public readonly int $outputs;

    /**
     * @var string[]
     */
    public readonly array $cmses;

    /**
     * @var array<string, int|string>
     */
    public readonly array $cmsesMap;

    public readonly bool $mifare;

    public readonly string $class;

    public function __construct(string $title, string $vendor, string $model, string $syslog, string $camera, int $outputs, array $cmses, array $cmsesMap, bool $mifare, string $class)
    {
        $this->title = $title;
        $this->vendor = $vendor;
        $this->model = $model;

        $this->syslog = $syslog;
        $this->camera = $camera;

        $this->outputs = $outputs;

        $this->cmses = $cmses;
        $this->cmsesMap = $cmsesMap;

        $this->mifare = $mifare && env('MIFARE_SECTOR', 0) > 0;

        $this->class = $class;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,
            'model' => $this->model,

            'syslog' => $this->syslog,
            'camera' => $this->camera,

            'outputs' => $this->outputs,

            'cmses' => $this->cmses,

            'class' => $this->class
        ];
    }

    public static function modelsToArray(): array
    {
        return array_map(static fn(IntercomModel $model) => $model->toArray(), self::models());
    }

    /**
     * @return IntercomModel[]
     */
    public static function models(): array
    {
        if (!isset(self::$models))
            self::$models = [
                'iscomx1' => new IntercomModel(
                    'IS ISCOM X1',
                    'IS',
                    'ISCOM X1',
                    'is',
                    'is',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VISIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    true,
                    IsIntercom::class
                ),
                'iscomx5' => new IntercomModel(
                    'IS ISCOM X5',
                    'IS',
                    'ISCOM X5',
                    'is',
                    'is',
                    1,
                    ['bk-100', 'com-100u', 'com-220u', 'factorial_8x8', 'kkm-100s2', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['BK-100' => 'VIZIT', 'KMG-100' => 'CYFRAL', 'KKM-100S2' => 'CYFRAL', 'KM100-7.1' => 'ELTIS', 'KM100-7.5' => 'ELTIS', 'COM-100U' => 'METAKOM', 'COM-220U' => 'METAKOM', 'FACTORIAL_8X8' => 'FACTORIAL'],
                    true,
                    IsIntercom::class
                ),
                'dks15102' => new IntercomModel(
                    'BEWARD DKS15102',
                    'BEWARD',
                    'DKS15102',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    false,
                    DksIntercom::class
                ),
                'dks15103' => new IntercomModel(
                    'BEWARD DKS15103',
                    'BEWARD',
                    'DKS15103',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    false,
                    DksIntercom::class
                ),
                'dsk15103_52701' => new IntercomModel(
                    'BEWARD DKS15103_rev5.2.7.0.1',
                    'BEWARD',
                    'DKS15103_rev5.2.7.0.1',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    true,
                    DksIntercom::class
                ),
                'dks15104' => new IntercomModel(
                    'BEWARD DKS15104',
                    'BEWARD',
                    'DKS15104',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105', 'kkm-108'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2, 'KKM-108' => 19],
                    true,
                    DksIntercom::class
                ),
                'dks15105' => new IntercomModel(
                    'BEWARD DKS15105',
                    'BEWARD',
                    'DKS15105',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kad2502', 'kkm-100s2', 'kkm-105', 'kkm-108'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KKM-108' => 3, 'KAD2501' => 2, 'KAD2502' => 4],
                    true,
                    DksIntercom::class
                ),
                'dks15122' => new IntercomModel(
                    'BEWARD DKS15122',
                    'BEWARD',
                    'DKS15122',
                    'beward',
                    'beward',
                    3,
                    ['kad2501', 'kkm-100s2', 'kkm-105'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    false,
                    DksIntercom::class
                ),
                'dks15374' => new IntercomModel(
                    'BEWARD DKS15374',
                    'BEWARD',
                    'DKS15374',
                    'beward',
                    'beward',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    true,
                    DksIntercom::class
                ),
                'dks15374_is10' => new IntercomModel(
                    'BEWARD DKS15374 IS10',
                    'BEWARD',
                    'DKS15374 IS10',
                    'beward',
                    'beward',
                    2,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['COM-25U' => 0, 'COM-80U' => 1, 'COM-100U' => 2, 'COM-160U' => 3, 'COM-220U' => 4, 'BK-30' => 5, 'BK-100' => 6, 'BK-400' => 7, 'KMG-100' => 8, 'KMG-100I' => 9, 'KM20-1' => 10, 'KM100-7.1' => 11, 'KM100-7.2' => 12, 'KM100-7.3' => 13, 'KM100-7.5' => 14, 'KKM-100S2' => 15, 'KKM-105' => 16, 'KKM-108' => 19, 'Factorial8x8' => 17, 'KAD2501' => 18],
                    true,
                    DksIntercom::class
                ),
                'dks20210' => new IntercomModel(
                    'BEWARD DKS20210',
                    'BEWARD',
                    'DKS20210',
                    'beward',
                    'beward',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KAD2501' => 2],
                    true,
                    DksIntercom::class
                ),
                'dks977957' => new IntercomModel(
                    'BEWARD DKS977957',
                    'BEWARD',
                    'DKS977957',
                    'beward',
                    'beward',
                    1,
                    ['bk-100', 'com-25u', 'com-100u', 'com-220u', 'kad2501', 'kkm-100s2', 'kkm-105', 'km100-7.1', 'km100-7.5', 'kmg-100'],
                    ['KKM-100S2' => 0, 'KKM-105' => 1, 'KKM-108' => 2, 'KAD2501' => 3, 'KAD2502' => 4],
                    true,
                    MifareDksIntercom::class
                ),
                'kv6113' => new IntercomModel(
                    'HikVision DS-KV6113',
                    'HIKVISION',
                    'DS-KV6113',
                    'hikVision',
                    'hikVision',
                    1,
                    [],
                    [],
                    true,
                    HikVisionIntercom::class
                ),
                'ds06ap' => new IntercomModel(
                    'BEWARD DS06A(P)',
                    'BEWARD',
                    'DS06A(P)',
                    'beward_ds',
                    'beward',
                    3,
                    [],
                    [],
                    true,
                    DsIntercom::class
                ),
                'ds06m' => new IntercomModel(
                    'BEWARD DS06M',
                    'BEWARD',
                    'DS06M',
                    'beward_ds',
                    'beward',
                    3,
                    [],
                    [],
                    true,
                    DsIntercom::class
                )
            ];

        return self::$models;
    }

    public static function model(string $value): ?IntercomModel
    {
        if (array_key_exists($value, self::models()))
            return self::$models[$value];

        return null;
    }
}