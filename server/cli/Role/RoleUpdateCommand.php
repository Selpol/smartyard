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
            'subscribers-subscriber-get' => ['subscriber-show-get'],
            'subscribers-subscriberCameras-post' => ['house-flat-camera-store-post'],
            'subscribers-subscriberCameras-delete' => ['house-flat-camera-delete-delete'],
            'subscribers-flatCameras-delete' => ['house-flat-camera-delete-delete'],
            'subscribers-key-get' => ['key-show-get'],
            'subscribers-key-put' => ['key-store-post'],
            'subscribers-key-post' => ['key-update-put'],
            'subscribers-key-delete' => ['key-delete-delete'],
            'subscribers-subscribers-get' => ['subscriber-index-get', 'house-flat-key-index-get', 'house-flat-camera-index-get'],
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
                    $sourcePermission->roles()->remove($role);

                    if (!$destinationPermission->roles()->has($role)) {
                        $destinationPermission->roles()->add($role);
                    }
                }
            }
        }
    }
}
