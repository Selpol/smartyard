<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Ip\Camera\Beward\BewardCamera;
use Selpol\Device\Ip\Camera\Fake\FakeCamera;
use Selpol\Device\Ip\Camera\HikVision\HikVisionCamera;
use Selpol\Device\Ip\Camera\Is\IsCamera;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Config\ConfigResolver;

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
            'vendor' => $this->vendor
        ];
    }

    public function instance(DeviceCamera $camera, ConfigResolver $resolver): CameraDevice
    {
        $class = $resolver->string('class');
        $class = match ($class) {
            'Is' => IsCamera::class,
            'Beward' => BewardCamera::class,
            'HikVision' => HikVisionCamera::class,
            'Fake' => FakeCamera::class
        };

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
                'is' => new CameraModel('IS', 'IS', 'class=Is'),
                'beward' => new CameraModel('BEWARD', 'BEWARD', 'class=Beward'),
                'hikVision' => new CameraModel('HikVision', 'HIKVISION', 'class=HikVision'),

                'fake' => new CameraModel('FAKE', 'FAKE', 'class=Fake')
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