<?php declare(strict_types=1);

use Selpol\Cache\FileCache;
use Selpol\Cache\RedisCache;
use Selpol\Container\ContainerConfigurator;
use Selpol\Entity\Repository\AuditRepository;
use Selpol\Entity\Repository\Core\CoreUserRepository;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Entity\Repository\Device\DeviceCameraRepository;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Entity\Repository\Dvr\DvrRecordRepository;
use Selpol\Entity\Repository\Dvr\DvrServerRepository;
use Selpol\Entity\Repository\Frs\FrsFaceRepository;
use Selpol\Entity\Repository\Frs\FrsServerRepository;
use Selpol\Entity\Repository\Inbox\InboxMessageRepository;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Entity\Repository\RoleRepository;
use Selpol\Entity\Repository\TaskRepository;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Feature\Address\Internal\InternalAddressFeature;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Archive\Internal\InternalArchiveFeature;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Audit\Internal\InternalAuditFeature;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Feature\Authentication\Internal\InternalAuthenticationFeature;
use Selpol\Feature\Authorization\AuthorizationFeature;
use Selpol\Feature\Authorization\Internal\InternalAuthorizationFeature;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Camera\Internal\InternalCameraFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\Dvr\Internal\InternalDvrFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\Mongo\MongoFileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\Frs\Internal\InternalFrsFeature;
use Selpol\Feature\Geo\DaData\DaDataGeoFeature;
use Selpol\Feature\Geo\GeoFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\House\Internal\InternalHouseFeature;
use Selpol\Feature\Inbox\Internal\InternalInboxFeature;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Feature\Monitor\Internal\InternalMonitorFeature;
use Selpol\Feature\Monitor\MonitorFeature;
use Selpol\Feature\Mqtt\Internal\InternalMqttFeature;
use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Feature\Oauth\Internal\InternalOauthFeature;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Feature\Plog\ClickHouse\ClickHousePlogFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Feature\Push\Internal\InternalPushFeature;
use Selpol\Feature\Push\PushFeature;
use Selpol\Feature\Role\Internal\InternalRoleFeature;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Feature\Sip\Internal\InternalSipFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\Task\Internal\InternalTaskFeature;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Feature\User\Internal\InternalUserFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\AuthService;
use Selpol\Service\ClientService;
use Selpol\Service\DatabaseService;
use Selpol\Service\DeviceService;
use Selpol\Service\FrsService;
use Selpol\Service\HttpService;
use Selpol\Service\PrometheusService;
use Selpol\Service\RedisService;
use Selpol\Service\TaskService;

return static function (ContainerConfigurator $builder) {
    //#region Services
    $builder->singleton(DatabaseService::class);
    $builder->singleton(RedisService::class);
    $builder->singleton(TaskService::class);

    $builder->singleton(ClientService::class);
    $builder->singleton(HttpService::class);

    $builder->singleton(DeviceService::class);

    $builder->singleton(AuthService::class);

    $builder->singleton(FrsService::class);

    $builder->singleton(PrometheusService::class);
    //#endregion

    //#region Features
    $builder->singleton(AuthenticationFeature::class, InternalAuthenticationFeature::class);

    $builder->singleton(RoleFeature::class, InternalRoleFeature::class);
    $builder->singleton(AuditFeature::class, InternalAuditFeature::class);

    $builder->singleton(OauthFeature::class, InternalOauthFeature::class);
    $builder->singleton(PushFeature::class, InternalPushFeature::class);

    $builder->singleton(MonitorFeature::class, InternalMonitorFeature::class);
    $builder->singleton(TaskFeature::class, InternalTaskFeature::class);

    $builder->singleton(SipFeature::class, InternalSipFeature::class);
    $builder->singleton(GeoFeature::class, DaDataGeoFeature::class);

    $builder->singleton(FileFeature::class, MongoFileFeature::class);

    $builder->singleton(ArchiveFeature::class, InternalArchiveFeature::class);
    $builder->singleton(AddressFeature::class, InternalAddressFeature::class);
    $builder->singleton(CameraFeature::class, InternalCameraFeature::class);
    $builder->singleton(InboxFeature::class, InternalInboxFeature::class);
    $builder->singleton(HouseFeature::class, InternalHouseFeature::class);
    $builder->singleton(UserFeature::class, InternalUserFeature::class);
    $builder->singleton(PlogFeature::class, ClickHousePlogFeature::class);
    $builder->singleton(DvrFeature::class, InternalDvrFeature::class);
    $builder->singleton(FrsFeature::class, InternalFrsFeature::class);

    $builder->singleton(MqttFeature::class, InternalMqttFeature::class);
    //#endregion

    //#region Repositories
    $builder->singleton(RoleRepository::class);
    $builder->singleton(PermissionRepository::class);
    $builder->singleton(AuditRepository::class);
    $builder->singleton(TaskRepository::class);

    $builder->singleton(CoreVarRepository::class);
    $builder->singleton(CoreUserRepository::class);

    $builder->singleton(DeviceCameraRepository::class);
    $builder->singleton(DeviceIntercomRepository::class);

    $builder->singleton(DvrServerRepository::class);
    $builder->singleton(DvrRecordRepository::class);

    $builder->singleton(FrsServerRepository::class);
    $builder->singleton(FrsFaceRepository::class);

    $builder->singleton(InboxMessageRepository::class);
    //#endregion

    $builder->singleton(FileCache::class);
    $builder->singleton(RedisCache::class);
};