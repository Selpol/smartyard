<?php declare(strict_types=1);

namespace Selpol\Cli\Role;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('role:init', 'Обновление ролей и прав')]
class RoleInitCommand
{
    #[Execute]
    public function execute(): void
    {
        $requirePermissions = [
            'block-flat-billing-delete' => '[Блокировка-Квартира] Удалить блокировку биллинга',
            'block-subscriber-billing-delete' => '[Блокировка-Абонент] Удалить блокировку биллинга',

            'addresses-location-get' => '[Адрес] Локации',

            'intercom-hidden' => '[Домофон] Доступ к скрытым устройствам',
            'camera-hidden' => '[Камера] Доступ к скрытым устройствам',

            'mqtt-access' => '[MQTT] Доступ к MQTT',

            'mobile-mask' => '[Телефон] Возможность видеть телефон',

            'intercom-web-call' => '[Веб-Домофон] Сделать звонок с браузера',
            'device-web-redirect' => '[Веб-Устройство] Перейти на устройство с браузера',

            'block-index-get' => '[Блокировка] Получить список',

            'block-flat-index-get' => '[Блокировка-Квартира] Получить список',
            'block-flat-store-get' => '[Блокировка-Квартира] Добавить блокировку',
            'block-flat-update-get' => '[Блокировка-Квартира] Обновить блокировку',
            'block-flat-delete-get' => '[Блокировка-Квартира] Удалить блокировку',

            'block-subscriber-index-get' => '[Блокировка-Абонент] Получить список',
            'block-subscriber-store-get' => '[Блокировка-Абонент] Добавить блокировку',
            'block-subscriber-update-get' => '[Блокировка-Абонент] Обновить блокировку',
            'block-subscriber-delete-get' => '[Блокировка-Абонент] Удалить блокировку',

            'config-index-get' => '[Конфигурация] Получить параметры конфигурации',
            'config-intercom-get' => '[Конфигурация] Получить конфигурацию домофона',
            'config-camera-get' => '[Конфигурация] Получить конфигурацию камеры',

            'dvr-index-get' => '[Dvr] Получить список камер на сервере',
            'dvr-show-get' => '[Dvr] Найти идентификатор камеры',

            'geo-index-get' => '[Гео] Получить список адресов',

            'log-index-get' => '[Логи] Получить логи',

            'monitor-index-get' => '[Мониторинг] Запросить статус устройств',

            'plog-index-get' => '[События] Получить список',
            'plog-camshot-get' => '[События] Получить скриншот'
        ];

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

                                            continue;
                                        } elseif ($titlePermissions[$title]->description != $description) {
                                            $titlePermissions[$title]->description = $description;
                                            $titlePermissions[$title]->update();
                                        }

                                        unset($titlePermissions[$title]);

                                        if (array_key_exists($title, $requirePermissions)) {
                                            unset($requirePermissions[$title]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($requirePermissions as $title => $description) {
            if (check($title, $filter)) {
                if (!array_key_exists($title, $titlePermissions)) {
                    $permission = new Permission();
                    $permission->title = $title;
                    $permission->description = $description;
                    $permission->insert();

                    continue;
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