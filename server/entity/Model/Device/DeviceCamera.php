<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use PDO;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
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
 * @property int|null $common
 *
 * @property string|null $ip
 *
 * @property string|null $comment
 *
 * @property string|null $device_id
 * @property string|null $device_model
 * @property string|null $device_software_version
 * @property string|null $device_hardware_version
 *
 * @property string|null $config
 *
 * @property bool $hidden
 * 
 * @property-read DvrServer|null $dvr_server
 * @property-read FrsServer|null $frs_server
 * 
 * @property-read HouseEntrance[] $entrances
 * @property-read HouseSubscriber[] $subscribers
 * 
 * @property-read DvrRecord[] $records
 */
class DeviceCamera extends Entity
{
    /**
     * @use RepositoryTrait<DeviceCameraRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'cameras';

    public static string $columnId = 'camera_id';

    /**
     * @return OneToOneRelationship<DvrServer>
     */
    public function dvr_server(): OneToOneRelationship
    {
        return $this->oneToOne(DvrServer::class, 'id', 'dvr_server_id');
    }

    /**
     * @return OneToOneRelationship<FrsServer>
     */
    public function frs_server(): OneToOneRelationship
    {
        return $this->oneToOne(FrsServer::class, 'id', 'frs_server_id');
    }

    /**
     * @return OneToManyRelationship<HouseEntrance>
     */
    public function entrances(): OneToManyRelationship
    {
        return $this->oneToMany(HouseEntrance::class, 'camera_id', 'camera_id');
    }

    /**
     * @return ManyToManyRelationship<HouseSubscriber[]>
     */
    public function subscribers(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseSubscriber::class, 'houses_cameras_subscribers', localRelation: 'camera_id', foreignRelation: 'house_subscriber_id');
    }

    /**
     * @return OneToManyRelationship<DvrRecord>
     */
    public function records(): OneToManyRelationship
    {
        return $this->oneToMany(DvrRecord::class, 'camera_id', 'camera_id');
    }

    public function getDvrServer(): ?DvrServer
    {
        if ($this->dvr_server_id) {
            return DvrServer::findById($this->dvr_server_id);
        }

        return null;
    }

    public function checkAllAccessForSubscriber(array $subscriber): bool
    {
        $params = ['camera_id' => $this->camera_id, 'house_subscriber_id' => $subscriber['subscriberId']];
        $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_subscribers WHERE camera_id = :camera_id AND house_subscriber_id = :house_subscriber_id');

        if ($statement && $statement->execute($params) && $statement->rowCount() == 1 && $statement->fetch(PDO::FETCH_NUM)[0] == 1) {
            return true;
        }

        foreach ($subscriber['flats'] as $flat) {
            $params = ['camera_id' => $this->camera_id, 'house_flat_id' => $flat['flatId']];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_flats WHERE camera_id = :camera_id AND house_flat_id = :house_flat_id');

            if ($statement && $statement->execute($params) && $statement->rowCount() == 1 && $statement->fetch(PDO::FETCH_NUM)[0] == 1) {
                return true;
            }

            $params = ['camera_id' => $this->camera_id, 'address_house_id' => $flat['addressHouseId']];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_houses WHERE camera_id = :camera_id AND address_house_id = :address_house_id');

            if ($statement && $statement->execute($params) && $statement->rowCount() == 1 && $statement->fetch(PDO::FETCH_NUM)[0] == 1) {
                return true;
            }

            $entrances = container(HouseFeature::class)->getEntrances('flatId', $flat['flatId']);

            foreach ($entrances as $entrance)
                if ($entrance['cameraId'] == $this->camera_id) {
                    return true;
                }
        }

        return false;
    }

    public function checkAccessForSubscriber(array $subscriber, ?int $houseId, ?int $flatId, ?int $entranceId): bool
    {
        if (!is_null($flatId)) {
            if (is_null($entranceId)) {
                $params = ['camera_id' => $this->camera_id, 'house_flat_id' => $flatId];
                $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_flats WHERE camera_id = :camera_id AND house_flat_id = :house_flat_id');
            } else {
                $entrance = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_entrances_flats WHERE house_entrance_id = :house_entrance_id AND house_flat_id = :house_flat_id');

                if (!$entrance || !$entrance->execute(['house_entrance_id' => $entranceId, 'house_flat_id' => $flatId])) {
                    return false;
                }

                if ($entrance->rowCount() != 1 || $entrance->fetch(PDO::FETCH_NUM)[0] != 1) {
                    return false;
                }

                $params = ['camera_id' => $this->camera_id, 'house_entrance_id' => $entranceId];
                $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_entrances WHERE camera_id = :camera_id AND house_entrance_id = :house_entrance_id');
            }
        } elseif (!is_null($houseId)) {
            $findFlatId = null;

            foreach ($subscriber['flats'] as $flat) {
                if ($flat['addressHouseId'] == $houseId) {
                    $findFlatId = $flat['flatId'];

                    break;
                }
            }

            if (is_null($findFlatId)) {
                return false;
            }

            $params = ['camera_id' => $this->camera_id, 'address_house_id' => $houseId];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_houses WHERE camera_id = :camera_id AND address_house_id = :address_house_id');
        } else {
            $params = ['camera_id' => $this->camera_id, 'house_subscriber_id' => $subscriber['subscriberId']];
            $statement = container(DatabaseService::class)->getConnection()->prepare('SELECT 1 FROM houses_cameras_subscribers WHERE camera_id = :camera_id AND house_subscriber_id = :house_subscriber_id');
        }

        return $statement && $statement->execute($params) && $statement->rowCount() == 1 && $statement->fetch(PDO::FETCH_NUM)[0] == 1;
    }

    public function toOldArray(): array
    {
        return $this->toArrayMap([
            "camera_id" => "cameraId",
            "dvr_server_id" => "dvrServerId",
            "frs_server_id" => "frsServerId",
            "enabled" => "enabled",
            "model" => "model",
            "url" => "url",
            "stream" => "stream",
            "credentials" => "credentials",
            "name" => "name",
            "dvr_stream" => "dvrStream",
            "timezone" => "timezone",
            "lat" => "lat",
            "lon" => "lon",
            "frs" => "frs",
            "common" => "common",
            "comment" => "comment"
        ]);
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

            'common' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'config' => rule()->string(),

            'device_id' => rule()->string()->clamp(0, 128),
            'device_model' => rule()->string()->clamp(0, 64),
            'device_software_version' => rule()->string()->clamp(0, 64),
            'device_hardware_version' => rule()->string()->clamp(0, 64),

            'hidden' => rule()->bool()
        ];
    }
}