<?php declare(strict_types=1);

namespace Selpol\Feature\Backup;

use Selpol\Feature\Backup\Internal\InternalBackupFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalBackupFeature::class)]
abstract readonly class BackupFeature extends Feature
{
    public const TABLES = [
        'core_vars',
        'core_users',
        'core_auth',

        'addresses_regions',
        'addresses_areas',
        'addresses_cities',
        'addresses_settlements',
        'addresses_streets',
        'addresses_houses',

        'houses_domophones',
        'houses_entrances',
        'houses_entrances_cmses',
        'houses_houses_entrances',
        'houses_flats',
        'houses_entrances_flats',
        'houses_rfids',
        'houses_subscribers_mobile',
        'houses_flats_subscribers',
        'houses_cameras_houses',
        'houses_cameras_flats',
        'houses_cameras_subscribers',

        'cameras',

        'camera_records',

        'inbox',

        'frs_faces',
        'frs_links_faces',

        'role',
        'permission',
        'role_permission',
        'user_role',
        'user_permission',

        'dvr_servers',
        'frs_servers',

        'sip_servers',

        'sip_user',

        'contractor'
    ];

    public const SEQUENCES = [
        'core_vars_var_id_seq',
        'core_users_uid_seq',
        'core_auth_id_seq',

        'addresses_areas_address_area_id_seq',
        'addresses_cities_address_city_id_seq',
        'addresses_houses_address_house_id_seq',
        'addresses_regions_address_region_id_seq',
        'addresses_settlements_address_settlement_id_seq',
        'addresses_streets_address_street_id_seq',

        'houses_domophones_house_domophone_id_seq',
        'houses_entrances_house_entrance_id_seq',
        'houses_flats_house_flat_id_seq',
        'houses_rfids_house_rfid_id_seq',
        'houses_subscribers_mobile_house_subscriber_id_seq',

        'cameras_camera_id_seq',

        'camera_records_record_id_seq',

        'inbox_msg_id_seq',

        'role_id_seq',
        'permission_id_seq',

        'dvr_servers_id_seq',
        'frs_servers_id_seq',

        'sip_servers_id_seq',

        'sip_user_id_seq',

        'contractor_id_seq'
    ];

    public abstract function backup(string $path): bool;

    public abstract function restore(string $path): bool;
}