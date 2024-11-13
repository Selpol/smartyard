<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\Intercom\Beward\DksIntercom;
use Selpol\Device\Ip\Intercom\Beward\DsIntercom;
use Selpol\Device\Ip\Intercom\HikVision\HikVisionIntercom;
use Selpol\Device\Ip\Intercom\Is\Is5Intercom;
use Selpol\Device\Ip\Intercom\Is\IsIntercom;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigResolver;

class IntercomModel
{
    /**
     * @var IntercomModel[]
     */
    private static array $models;

    public function __construct(
        public readonly string $title,
        public readonly string $vendor,
        public readonly string $config
    )
    {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor
        ];
    }

    public function isIs(): bool
    {
        return $this->vendor === 'IS';
    }

    public function isBeward(): bool
    {
        return $this->vendor === 'BEWARD';
    }

    public function isHikVision(): bool
    {
        return $this->vendor === 'HIKVISION';
    }

    public function instance(DeviceIntercom $intercom, ConfigResolver $resolver): IntercomDevice
    {
        $class = $resolver->string('class');
        $class = match ($class) {
            'DksBeward' => DksIntercom::class,
            'DsBeward' => DsIntercom::class,
            'HikVision' => HikVisionIntercom::class,
            'Is' => IsIntercom::class,
            'Is5' => Is5Intercom::class

        };

        return new $class(uri($intercom->url), $intercom->credentials, $this, $intercom, $resolver);
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
                'is' => new IntercomModel('IS ISCOM X1', 'IS', 'class=Is'),
                'is_2' => new IntercomModel('IS ISCOM X5', 'IS', 'class=Is5'),
                'beward_ds' => new IntercomModel('BEWARD DS', 'BEWARD', 'class=DsBeward'),
                'beward_dks' => new IntercomModel('BEWARD DKS', 'BEWARD', 'class=DksBeward'),
                'hikvision' => new IntercomModel('HikVision', 'HIKVISION', 'class=HikVision'),

                'iscomx1' => new IntercomModel('IS ISCOM X1', 'IS', 'class=Is'),
                'iscomx1_2' => new IntercomModel('IS ISCOM X1 2.2.5.10.5', 'IS', 'class=Is'),
                'iscomx5' => new IntercomModel('IS ISCOM X5', 'IS', 'class=Is5'),
                'iscomx5_rfid' => new IntercomModel('IS ISCOM X5 RFID', 'IS', 'mifare=false\nclass=Is5'),

                'dks15374' => new IntercomModel('BEWARD DKS15374', 'BEWARD', 'class=DksBeward'),
                'dks15374_rev5.2.8.2.1' => new IntercomModel('BEWARD DKS15374_rev5.2.8.2.1', 'BEWARD', 'auth=basic\nclass=DksBeward'),
                'dks15374_rfid' => new IntercomModel('BEWARD DKS15374 RFID', 'BEWARD', 'class=DksBeward\nmifare=false'),
                'dks15374_is10' => new IntercomModel('BEWARD DKS15374 IS10', 'BEWARD', 'class=DksBeward'),
                'dks977957' => new IntercomModel('BEWARD DKS977957', 'BEWARD', 'class=DksBeward'),
                'dks977957_rev5.2.3.9.3' => new IntercomModel('BEWARD DKS977957_rev5.2.3.9.3', 'BEWARD', 'auth=basic\nclass=DksBeward'),
                'kv6113' => new IntercomModel('HikVision DS-KV6113', 'HIKVISION', 'class=HikVision')
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