<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $camera_id
 *
 * @property int $enabled
 *
 * @property string $model
 * @property string $url
 * @property string|null $stream
 * @property string $credentials
 * @property string|null $name
 * @property string|null $dvr_stream
 * @property string|null $timezone
 *
 * @property double|null $lat
 * @property double|null $lon
 *
 * @property double|null $direction
 * @property double|null $angle
 * @property double|null $distance
 *
 * @property string|null $frs
 *
 * @property int|null $md_left
 * @property int|null $md_top
 * @property int|null $md_width
 * @property int|null $md_height
 *
 * @property int|null $common
 *
 * @property string|null $ip
 *
 * @property string|null $comment
 */
class DeviceCamera extends Entity
{
    public static string $table = 'cameras';

    public static string $columnId = 'camera_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'enabled' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'model' => [Rule::required(), Rule::in(array_keys(CameraModel::models())), Rule::nonNullable()],
            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],
            'stream' => [Rule::length()],
            'credentials' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'name' => [Rule::length()],
            'dvr_stream' => [Rule::length()],
            'timezone' => [Rule::length()],

            'lat' => [Rule::float()],
            'lon' => [Rule::float()],

            'direction' => [Rule::float()],
            'angle' => [Rule::float()],
            'distance' => [Rule::float()],

            'frs' => [Rule::length()],

            'md_left' => [Rule::int()],
            'md_top' => [Rule::int()],
            'md_width' => [Rule::int()],
            'md_height' => [Rule::int()],

            'common' => [Rule::int()],

            'ip' => [Rule::ipV4()],

            'comment' => [Rule::length()]
        ];
    }
}