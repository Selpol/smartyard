<?php declare(strict_types=1);

namespace Selpol\Cli\Role;

use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Router\Trait\RouterTrait;

#[Executable('role:init', 'Обновление ролей и прав')]
class RoleInitCommand
{
    #[Execute]
    public function execute(): void
    {
        /** @var array<string, Permission> $titlePermissions */
        $titlePermissions = array_reduce(Permission::fetchAll(), static function (array $previous, Permission $current) {
            $previous[$current->title] = $current;

            return $previous;
        }, []);

        $dir = path('controller/Api');
        $apis = scandir($dir);

        $filter = config_get('feature.role.filter_permissions', ['*']);

        function check(string $title, array $filter): bool
        {
            if (in_array('*', $filter)) {
                return true;
            }

            if (in_array($title, $filter)) {
                return true;
            }

            if (in_array('!' . $title, $filter)) {
                return false;
            }

            $segments = explode('-', $title);
            $counter = count($segments);

            for ($i = 0; $i + 1 < $counter; ++$i) {
                $item = implode('-', array_slice($segments, 0, $i + 1));

                if (in_array($item . '-*', $filter)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($apis as $api) {
            if ($api !== "." && $api !== ".." && is_dir($dir . ('/' . $api))) {
                $methods = scandir($dir . ('/' . $api));

                foreach ($methods as $method) {
                    if ($method !== "." && $method !== ".." && str_ends_with($method, ".php") && is_file($dir . sprintf('/%s/%s', $api, $method))) {
                        $method = substr($method, 0, -4);

                        require_once $dir . sprintf('/%s/%s.php', $api, $method);

                        /** @var class-string<Api> $class */
                        $class = sprintf('Selpol\Controller\Api\%s\%s', $api, $method);

                        if (class_exists($class)) {
                            $request_methods = $class::index();

                            if ($request_methods) {
                                $keys = array_keys($request_methods);

                                foreach ($keys as $key) {
                                    $title = $api . '-' . $method . '-' . strtolower(is_int($key) ? $request_methods[$key] : $key);
                                    $description = is_int($key) ? $title : $request_methods[$key];

                                    if (check($title, $filter)) {
                                        if (!array_key_exists($title, $titlePermissions)) {
                                            $permission = new Permission();
                                            $permission->title = $title;
                                            $permission->description = $description;
                                            $permission->insert();
                                        } elseif ($titlePermissions[$title]->description != $description) {
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
        }

        if (file_exists(path('config/admin.php'))) {
            $router = new class {
                use RouterTrait;

                public function __construct()
                {
                    $this->loadRouter(false, 'admin');
                }
            };

            function walk(string $method, array $routes, array &$permissions, array $filter): void
            {
                foreach ($routes as $childRoutes) {
                    if (array_key_exists('class', $childRoutes)) {
                        /** @var class-string<AdminRbtController> $class */
                        $class = $childRoutes['class'][0];
                        $scopes = $class::scopes();

                        foreach ($scopes as $title => $description) {
                            if (check($title, $filter)) {
                                if (!array_key_exists($title, $permissions)) {
                                    $permission = new Permission();
                                    $permission->title = $title;
                                    $permission->description = $description;
                                    $permission->insert();
                                } elseif ($permissions[$title]->description != $description) {
                                    $permissions[$title]->description = $description;
                                    $permissions[$title]->update();
                                }

                                unset($permissions[$title]);
                            }
                        }
                    } else {
                        walk($method, $childRoutes, $permissions, $filter);
                    }
                }
            }

            $routes = $router->getRouter()->getRoutes();

            foreach ($routes as $method => $childRoutes) {
                walk($method, $childRoutes, $titlePermissions, $filter);
            }
        }

        $requirePermissions = [
            'block-flat-billing-delete' => '[Блокировка-Квартира] Удалить блокировку биллинга',
            'block-subscriber-billing-delete' => '[Блокировка-Абонент] Удалить блокировку биллинга',

            'addresses-location-get' => '[Адрес] Локации',

            'intercom-hidden' => '[Домофон] Доступ к скрытым устройствам',
            'camera-hidden' => '[Камера] Доступ к скрытым устройствам',

            'mqtt-access' => '[MQTT] Доступ к MQTT',

            'mobile-mask' => '[Телефон] Возможность видеть телефон',

            'intercom-web-call' => '[Веб-Домофон] Сделать звонок с браузера',
            'device-web-redirect' => '[Веб-Устройство] Перейти на устройство с браузера'
        ];

        foreach ($requirePermissions as $title => $description) {
            if (check($title, $filter)) {
                if (!array_key_exists($title, $titlePermissions)) {
                    $permission = new Permission();
                    $permission->title = $title;
                    $permission->description = $description;
                    $permission->insert();
                } elseif ($titlePermissions[$title]->description != $description) {
                    $titlePermissions[$title]->description = $description;
                    $titlePermissions[$title]->update();
                }

                unset($titlePermissions[$title]);
            }
        }

        foreach ($titlePermissions as $permission) {
            $permission->delete();
        }
    }
}