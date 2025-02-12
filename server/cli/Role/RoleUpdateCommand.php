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
            'server-frs-delete' => ['server-frs-delete-delete']
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
