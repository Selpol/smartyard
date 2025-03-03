<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Kernel\Exception\KernelException;

class CameraModel
{
    /**
     * @var CameraModel[]
     */
    private static array $models;

    public function __construct(public readonly string $title, public readonly string $vendor, public readonly string $config)
    {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,
            'config' => $this->config
        ];
    }

    public function instance(DeviceCamera $camera, ConfigResolver $resolver): CameraDevice
    {
        $class = $resolver->string('class');

        if (!class_exists($class)) {
            throw new KernelException('Не известный обработчик камеры');
        }

        if (!is_subclass_of($class, CameraDevice::class)) {
            throw new KernelException('Обработчик не принадлежит камерам');
        }

        return new $class(uri($camera->url), $camera->credentials, $this, $camera, $resolver);
    }

    public static function modelsToArray(): array
    {
        return array_map(static fn(CameraModel $model): array => $model->toArray(), self::models());
    }

    /**
     * @return CameraModel[]
     */
    public static function models(): array
    {
        if (!isset(self::$models)) {
            self::$models = [
                'is' => new CameraModel('IS', 'IS', 'class=Selpol\Device\Ip\Camera\Is\IsCamera'),
                'beward' => new CameraModel('BEWARD', 'BEWARD', 'class=Selpol\Device\Ip\Camera\Beward\BewardCamera'),
                'hikVision' => new CameraModel('HikVision', 'HIKVISION', 'class=Selpol\Device\Ip\Camera\HikVision\HikVisionCamera'),

                'fake' => new CameraModel('FAKE', 'FAKE', 'class=Selpol\Device\Ip\Camera\Fake\FakeCamera')
            ];
        }

        return self::$models;
    }

    public static function model(string $value): ?CameraModel
    {
        if (array_key_exists($value, self::models())) {
            return self::$models[$value];
        }

        return null;
    }
}