<?php declare(strict_types=1);

namespace Selpol\Command\Role;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Service\DatabaseService;

#[Executable('role:init', 'Создать группы и права')]
class InitCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(DatabaseService $db): void
    {
        /** @var array<string, Permission> $titlePermissions */
        $titlePermissions = array_reduce(Permission::fetchAll(), static function (array $previous, Permission $current) {
            $previous[$current->title] = $current;

            return $previous;
        }, []);

        $dir = path('controller/Api');
        $apis = scandir($dir);

        foreach ($apis as $api) {
            if ($api != "." && $api != ".." && is_dir($dir . "/$api")) {
                $methods = scandir($dir . "/$api");

                foreach ($methods as $method) {
                    if ($method != "." && $method != ".." && str_ends_with($method, ".php") && is_file($dir . "/$api/$method")) {
                        $method = substr($method, 0, -4);

                        require_once $dir . "/$api/$method.php";

                        /** @var class-string<Api> $class */
                        $class = "Selpol\\Controller\\Api\\$api\\$method";

                        if (class_exists($class)) {
                            $request_methods = $class::index();

                            if ($request_methods) {
                                $keys = array_keys($request_methods);

                                foreach ($keys as $key) {
                                    $title = $api . '-' . $method . '-' . strtolower(is_int($key) ? $request_methods[$key] : $key);
                                    $description = is_int($key) ? $title : $request_methods[$key];

                                    if (!array_key_exists($title, $titlePermissions)) {
                                        $permission = new Permission();

                                        $permission->title = $title;
                                        $permission->description = $description;

                                        $permission->insert();
                                    } else if ($titlePermissions[$title]->description != $description) {
                                        $titlePermissions[$title]->description = $description;
                                        $titlePermissions[$title]->update();
                                    }

                                    unset($titlePermissions[$title]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $requirePermissions = [
            'block-flat-billing-delete' => '[Блокировка-Квартира] Удалить блокировку биллинга',
            'block-subscriber-billing-delete' => '[Блокировка-Абонент] Удалить блокировку биллинга',

            'addresses-location-get' => '[Адрес] Локации',

            'intercom-hidden' => '[Домофон] Доступ к скрытым устройствам',
            'camera-hidden' => '[Камера] Доступ к скрытым устройствам'
        ];

        foreach ($requirePermissions as $title => $description) {
            if (!array_key_exists($title, $titlePermissions)) {
                $permission = new Permission();

                $permission->title = $title;
                $permission->description = $description;

                $permission->insert();
            } else if ($titlePermissions[$title]->description != $description) {
                $titlePermissions[$title]->description = $description;
                $titlePermissions[$title]->update();
            }

            unset($titlePermissions[$title]);
        }

        foreach ($titlePermissions as $permission)
            $permission->delete();

        $this->getLogger()->debug('Роля и права успешно созданы');
    }
}