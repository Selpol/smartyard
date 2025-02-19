<?php declare(strict_types=1);

namespace Selpol\Cli\Role;

use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('role:update', 'Обновить права у ролей')]
class RoleUpdateCommand
{
    #[Execute]
    public function execute(): void
    {
        $permissions = [
            'authentication-login-post' => ['authentication-store-post'],
            'authentication-logout-get' => ['authentication-update-put'],
            'authentication-permission-get' => ['authentication-index-get'],

            'authentication-whoAmI-get' => ['user-setting-index-get'],

            'sip-user-get' => ['sip-user-index-get'],
            'sip-user-post' => ['sip-user-store-post'],
            'sip-user-put' => ['sip-user-update-put'],
            'sip-user-delete' => ['sip-user-delete-delete'],

            'accounts-audit-get' => ['account-audit-index-get'],

            'group-group-get' => ['group-index-get'],
            'group-group-post' => ['group-show-get'],
            'group-group-put' => ['group-store-post'],
            'group-group-delete' => ['group-update-put'],
            'group-groups-get' => ['group-delete-delete'],

            'contractor-contractor-get' => ['contractor-show-get'],
            'contractor-contractor-post' => ['contractor-store-post'],
            'contractor-contractor-put' => ['contractor-update-put'],
            'contractor-contractor-delete' => ['contractor-delete-delete'],
            'contractor-contractors-get' => ['contractor-index-get'],
            'contractor-sync-get' => ['contractor-sync-get'],

            'subscribers-flatCameras-post' => ['house-flat-camera-store-post'],
            'subscribers-subscriber-get' => ['subscriber-show-get', 'subscriber-flat-index-get'],
            'subscribers-subscriberCameras-post' => ['house-flat-camera-store-post'],
            'subscribers-subscriberCameras-delete' => ['house-flat-camera-delete-delete'],
            'subscribers-flatCameras-delete' => ['house-flat-camera-delete-delete'],

            'subscribers-subscriber-post' => ['subscriber-store-post', 'subscriber-flat-store-post'],
            'subscribers-subscriber-put' => ['subscriber-update-put', 'subscriber-flat-store-post', 'subscriber-flat-update-put', 'subscriber-flat-delete-delete'],
            'subscribers-subscriber-delete' => ['subscriber-delete-delete', 'subscriber-flat-delete-delete'],

            'subscribers-key-get' => ['key-show-get'],
            'subscribers-key-put' => ['key-store-post'],
            'subscribers-key-post' => ['key-update-put'],
            'subscribers-key-delete' => ['key-delete-delete'],
            'subscribers-subscribers-get' => ['subscriber-index-get', 'house-flat-key-index-get', 'house-flat-camera-index-get', 'subscriber-flat-index-get'],

            'intercom-log-get' => ['intercom-log-index-get'],

            'role-permission-get' => ['permission-index-get'],
            'role-role-get' => ['role-index-get'],
            'role-role-post' => ['role-store-post'],
            'role-role-put' => ['role-update-put'],
            'role-role-delete' => ['role-delete-delete'],
            'role-rolePermission-get' => ['role-permission-index-get'],
            'role-rolePermission-post' => ['role-permission-store-post'],
            'role-rolePermission-delete' => ['role-permission-delete-delete'],
            'role-userPermission-get' => ['user-permission-index-get'],
            'role-userPermission-post' => ['user-permission-store-post'],
            'role-userPermission-delete' => ['user-permission-delete-delete'],
            'role-userRole-get' => ['user-role-index-get'],
            'role-userRole-post' => ['user-role-store-post'],
            'role-userRole-delete' => ['user-role-delete-delete'],

            'accounts-users-get' => ['user-index-get'],
            'accounts-user-get' => ['user-show-get'],
            'accounts-user-post' => ['user-store-post'],
            'accounts-user-put' => ['user-update-put'],
            'accounts-user-delete' => ['user-delete-delete'],

            'accounts-session-get' => ['user-session-show-get'],
            'accounts-session-put' => ['user-session-update-put'],

            'server-variable-get' => ['server-variable-index-get'],
            'server-variable-put' => ['server-variable-update-put'],

            'server-streamer-get' => ['server-streamer-index-get'],
            'server-streamer-post' => ['server-streamer-store-post'],
            'server-streamer-put' => ['server-streamer-update-put'],
            'server-streamer-delete' => ['server-streamer-delete-delete'],

            'server-sip-get' => ['server-sip-index-get'],
            'server-sip-post' => ['server-sip-store-post'],
            'server-sip-put' => ['server-sip-update-put'],
            'server-sip-delete' => ['server-sip-delete-delete'],

            'server-frs-get' => ['server-frs-index-get'],
            'server-frs-post' => ['server-frs-store-post'],
            'server-frs-put' => ['server-frs-update-put'],
            'server-frs-delete' => ['server-frs-delete-delete'],

            'server-dvr-get' => ['server-dvr-index-get'],
            'server-dvr-post' => ['server-dvr-store-post'],
            'server-dvr-put' => ['server-dvr-update-put'],
            'server-dvr-delete' => ['server-dvr-delete-delete'],

            'cameras-cameras-get' => ['camera-index-get'],
            'cameras-camera-get' => ['camera-show-get'],
            'cameras-screenshot-get' => ['camera-screenshot-get'],
            'cameras-camera-post' => ['camera-store-post'],
            'cameras-camera-put' => ['camera-update-put'],
            'cameras-camera-delete' => ['camera-delete-delete'],
            'cameras-model-get' => ['camera-model-index-get'],

            'intercom-sync-get' => ['intercom-device-sync-post'],
            'intercom-info-get' => ['intercom-device-info-get'],
            'intercom-intercom-get' => ['intercom-show-get'],
            'intercom-intercom-put' => ['intercom-update-put'],
            'intercom-intercom-post' => ['intercom-store-post'],
            'intercom-intercom-delete' => ['intercom-delete-delete'],
            'intercom-intercoms-get' => ['intercom-index-get'],
            'intercom-model-get' => ['intercom-model-index-get'],
            'intercom-level-get' => ['intercom-device-level-post'],
            'intercom-reboot-get' => ['intercom-device-reboot-post'],
            'intercom-reset-get' => ['intercom-device-reset-post'],
            'intercom-open-get' => ['intercom-device-open-post'],
            'intercom-call-get' => ['intercom-device-call-post'],
            'intercom-password-get' => ['intercom-device-password-post'],
            'intercom-stop-get' => ['intercom-device-call-post'],

            'addresses-region-get' => ['address-region-index-get', 'address-region-show-get'],
            'addresses-settlement-get' => ['address-settlement-index-get', 'address-settlement-show-get'],
            'addresses-street-get' => ['address-street-index-get', 'address-street-show-get'],
            'addresses-city-get' => ['address-city-index-get', 'address-city-show-get'],
            'addresses-area-get' => ['address-area-index-get', 'address-area-show-get'],
            'addresses-qr-post' => ['address-house-qr-get'],
            'addresses-region-put' => ['address-region-update-put'],
            'addresses-region-post' => ['address-region-store-post'],
            'addresses-region-delete' => ['address-region-delete-delete'],
            'addresses-settlement-put' => ['address-settlement-update-put'],
            'addresses-settlement-post' => ['address-settlement-store-post'],
            'addresses-settlement-delete' => ['address-settlement-delete-delete'],
            'addresses-street-put' => ['address-street-update-put'],
            'addresses-street-post' => ['address-street-store-post'],
            'addresses-street-delete' => ['address-street-delete-delete'],
            'addresses-city-put' => ['address-city-update-put'],
            'addresses-city-post' => ['address-city-store-post'],
            'addresses-city-delete' => ['address-city-delete-delete'],
            'addresses-addresses-get' => ['address-house-index-get', 'address-house-show-get'],
            'addresses-area-put' => ['address-area-update-put'],
            'addresses-area-post' => ['address-area-store-post'],
            'addresses-area-delete' => ['address-area-delete-delete'],
            'addresses-house-get' => ['address-house-index-get', 'address-house-show-get'],
            'addresses-house-put' => ['address-house-update-put'],
            'addresses-house-post' => ['address-house-store-post', 'address-house-magic-post'],
            'addresses-house-delete' => ['address-house-delete-delete'],
        ];

        foreach ($permissions as $source => $destinations) {
            $sourcePermission = Permission::fetch(criteria()->equal('title', $source));

            if (!$sourcePermission) {
                continue;
            }

            $roles = $sourcePermission->roles;

            foreach ($destinations as $destination) {
                $destinationPermission = Permission::fetch(criteria()->equal('title', $destination));

                if (!$destinationPermission) {
                    continue;
                }

                foreach ($roles as $role) {
                    if (!$role->permissions()->has($destinationPermission)) {
                        $role->permissions()->add($destinationPermission);
                    }
                }
            }
        }
    }
}
