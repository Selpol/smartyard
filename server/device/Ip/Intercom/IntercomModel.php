<?php

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\Intercom\Is\IsComX1Intercom;

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

    public readonly string $class;

    public function __construct(string $title, string $vendor, string $model, string $syslog, string $camera, int $outputs, array $cmses, string $class)
    {
        $this->title = $title;
        $this->vendor = $vendor;
        $this->model = $model;

        $this->syslog = $syslog;
        $this->camera = $camera;

        $this->outputs = $outputs;

        $this->cmses = $cmses;

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
                    IsComX1Intercom::class
                )
            ];

        return self::$models;
    }
}