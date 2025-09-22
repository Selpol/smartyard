<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Kernel\Exception\KernelException;

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
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,
            'config' => $this->config
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
        $class = $resolver->string(ConfigKey::Handler);

        if (!class_exists($class)) {
            throw new KernelException('Не известный обработчик домофона');
        }

        if (!is_subclass_of($class, IntercomDevice::class)) {
            throw new KernelException('Обработчик не принадлежит домофоном');
        }

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
                'auto' => new IntercomModel('Автоопределение', 'AUTO', 'class=Selpol\Device\Ip\Intercom\Auto\AutoIntercom'),
                'is_1' => new IntercomModel('IS ISCOM X1', 'IS', 'class=Selpol\Device\Ip\Intercom\Is\IsIntercom'),
                'is_5' => new IntercomModel('IS ISCOM X5', 'IS', 'class=Selpol\Device\Ip\Intercom\Is\Is5Intercom'),
                'beward_ds' => new IntercomModel('BEWARD DS', 'BEWARD', 'class=Selpol\Device\Ip\Intercom\Beward\DsIntercom'),
                'beward_dks' => new IntercomModel('BEWARD DKS', 'BEWARD', 'class=Selpol\Device\Ip\Intercom\Beward\DksIntercom'),
                'hikvision' => new IntercomModel('HikVision', 'HIKVISION', 'class=Selpol\Device\Ip\Intercom\HikVision\HikVisionIntercom'),
                'relay' => new IntercomModel('Relay', 'RX', 'class=Selpol\Device\Ip\Intercom\Relay\RelayIntercom')
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