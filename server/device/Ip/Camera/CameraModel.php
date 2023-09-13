<?php

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Ip\Camera\Beward\BewardCamera;
use Selpol\Device\Ip\Camera\Fake\FakeCamera;
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

    /**
     * @return CameraModel[]
     */
    public static function models(): array
    {
        if (!isset(self::$models))
            self::$models = [
                'is' => new CameraModel('IS DOMOPHONE CAMERA', 'IS', IsCamera::class),
                'beward' => new CameraModel('BEWARD DOMOPHONE CAMERA', 'BEWARD', BewardCamera::class),
                'fake' => new CameraModel('FAKE CAMERA', 'FAKE', FakeCamera::class)
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