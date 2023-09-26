<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Ip\Camera\Beward\BewardCamera;
use Selpol\Device\Ip\Camera\Fake\FakeCamera;
use Selpol\Device\Ip\Camera\HikVision\HikVisionCamera;
use Selpol\Device\Ip\Camera\Is\IsCamera;

class CameraModel
{
    /**
     * @var CameraModel[]
     */
    private static array $models;

    public readonly string $title;
    public readonly string $vendor;

    public readonly string $class;

    public function __construct(string $title, string $vendor, string $class)
    {
        $this->title = $title;
        $this->vendor = $vendor;

        $this->class = $class;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,

            'model' => false,
            'version' => false,

            'class' => $this->class
        ];
    }

    public static function modelsToArray(): array
    {
        return array_map(static fn(CameraModel $model) => $model->toArray(), self::models());
    }

    /**
     * @return CameraModel[]
     */
    public static function models(): array
    {
        if (!isset(self::$models))
            self::$models = [
                'is' => new CameraModel('Sokol', 'IS', IsCamera::class),
                'beward' => new CameraModel('Beward', 'BEWARD', BewardCamera::class),
                'hikVision' => new CameraModel('HikVision', 'HIKVISION', HikVisionCamera::class),

                'fake' => new CameraModel('Fake', 'FAKE', FakeCamera::class)
            ];

        return self::$models;
    }

    public static function model(string $value): ?CameraModel
    {
        if (array_key_exists($value, self::models()))
            return self::$models[$value];

        return null;
    }
}