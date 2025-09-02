<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr;

use Selpol\Device\Ip\Dvr\Flussonic\FlussonicDvr;
use Selpol\Device\Ip\Dvr\Trassir\TrassirDvr;

class DvrModel
{
    /**
     * @var DvrModel[]
     */
    private static array $models;


    public function __construct(public readonly string $title, public readonly string $vendor, public readonly string $class)
    {
    }

    public function isFlussonic(): bool
    {
        return $this->title == 'FLUSSONIC';
    }

    public function isTrassir(): bool
    {
        return $this->title == 'TRASSIR';
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'vendor' => $this->vendor,

            'class' => $this->class
        ];
    }

    public static function modelsToArray(): array
    {
        return array_map(static fn(DvrModel $model): array => $model->toArray(), self::models());
    }

    public static function models(): array
    {
        if (!isset(self::$models)) {
            self::$models = [
                'flussonic' => new DvrModel('FLUSSONIC', 'FLUSSONIC', FlussonicDvr::class),
                'trassir' => new DvrModel('TRASSIR', 'TRASSIR', TrassirDvr::class),
            ];
        }

        return self::$models;
    }

    public static function model(string $value): ?DvrModel
    {
        if (array_key_exists($value, self::models())) {
            return self::$models[$value];
        }

        return null;
    }
}