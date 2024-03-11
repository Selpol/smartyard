<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use PDO;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Repository\Device\DeviceCameraRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;
use Selpol\Service\DatabaseService;

/**
 * @property int $camera_id
 *
 * @property int|null $dvr_server_id
 * @property int|null $frs_server_id
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
 *
 * @property bool $hidden
 */
class DeviceCamera extends Entity
{
    /**
     * @use RepositoryTrait<DeviceCameraRepository>
     */
    use RepositoryTrait;

    public static string $table = 'cameras';

    public static string $columnId = 'camera_id';

    public function checkAccessForSubscriber(array $subscriber, ?int $houseId, ?int $flatId, ?int $entranceId): bool
    {
        if (!is_null($flatId)) {
            if ($this->checkFlatBlock($flatId))
                return false;

            if (is_null($entranceId)) {
                $params = ['camera_id' => $this->camera_id, 'house_flat_id' => $flatId];
                $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_flats WHERE camera_id = :camera_id AND house_flat_id = :house_flat_id');
            } else {
                $entrance = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_entrances_flats WHERE house_entrance_id = :house_entrance_id AND house_flat_id = :house_flat_id');

                if (!$entrance || !$entrance->execute(['house_entrance_id' => $entranceId, 'house_flat_id' => $flatId]))
                    return false;

                if ($entrance->rowCount() != 1 || $entrance->fetch(PDO::FETCH_NUM)[0] != 1)
                    return false;

                $params = ['camera_id' => $this->camera_id, 'house_entrance_id' => $entranceId];
                $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_entrances WHERE camera_id = :camera_id AND house_entrance_id = :house_entrance_id');
            }
        } else if (!is_null($houseId)) {
            $findFlatId = null;

            foreach ($subscriber['flats'] as $flat) {
                if ($flat['addressHouseId'] == $houseId) {
                    $findFlatId = $flat['flatId'];

                    break;
                }
            }

            if (is_null($findFlatId) || $this->checkFlatBlock($findFlatId))
                return false;

            $params = ['camera_id' => $this->camera_id, 'address_house_id' => $houseId];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_houses WHERE camera_id = :camera_id AND address_house_id = :address_house_id');
        } else {
            $params = ['camera_id' => $this->camera_id, 'house_subscriber_id' => $subscriber['subscriberId']];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_subscribers WHERE camera_id = :camera_id AND house_subscriber_id = :house_subscriber_id');
        }

        return $statement && $statement->execute($params) && $statement->rowCount() == 1 && $statement->fetch(PDO::FETCH_NUM)[0] == 1;
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'dvr_server_id' => rule()->int()->clamp(0),
            'frs_server_id' => rule()->int()->clamp(0),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(CameraModel::models()))->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'stream' => rule()->string(),
            'credentials' => rule()->required()->string()->nonNullable(),
            'name' => rule()->string(),
            'dvr_stream' => rule()->string(),
            'timezone' => rule()->string(),

            'lat' => rule()->float(),
            'lon' => rule()->float(),

            'direction' => rule()->float(),
            'angle' => rule()->float(),
            'distance' => rule()->float(),

            'md_left' => rule()->int(),
            'md_top' => rule()->int(),
            'md_width' => rule()->int(),
            'md_height' => rule()->int(),

            'common' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'hidden' => rule()->bool()
        ];
    }

    private function checkFlatBlock(int $flatId): bool
    {
        $flat = HouseFlat::findById($flatId, setting: setting()->columns(['auto_block', 'admin_block', 'manual_block']));

        return !$flat || $flat->auto_block | $flat->admin_block || $flat->manual_block;
    }
}