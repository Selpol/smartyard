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
            'block-flat-store-post' => '[Блокировка-Квартира] Добавить блокировку',
            'block-flat-update-put' => '[Блокировка-Квартира] Обновить блокировку',
            'block-flat-delete-delete' => '[Блокировка-Квартира] Удалить блокировку',

            'block-subscriber-index-get' => '[Блокировка-Абонент] Получить список',
            'block-subscriber-store-post' => '[Блокировка-Абонент] Добавить блокировку',
            'block-subscriber-update-put' => '[Блокировка-Абонент] Обновить блокировку',
            'block-subscriber-delete-delete' => '[Блокировка-Абонент] Удалить блокировку',

            'config-suggestion-get' => '[Конфигурация] Получить параметры конфигурации',
            'config-intercom-get' => '[Конфигурация] Получить конфигурацию домофона',
            'config-camera-get' => '[Конфигурация] Получить конфигурацию камеры',

            'dvr-index-get' => '[Dvr] Получить список камер на сервере',
            'dvr-show-get' => '[Dvr] Найти идентификатор камеры',

            'geo-index-get' => '[Гео] Получить список адресов',

            'log-index-get' => '[Логи] Получить логи',

            'monitor-index-get' => '[Мониторинг] Запросить статус устройств',

            'plog-index-get' => '[События] Получить список',
            'plog-camshot-get' => '[События] Получить скриншот',

            'task-index-get' => '[Задачи] Получить список задач',
            'task-search-get' => '[Задачи] Поиск по списку задач',

            'task-unique-get' => '[Задачи] Получить список уникальных задач',
            'task-unique-delete-delete' => '[Задачи] Удалить из списка уникальную задачу',

            'streamer-index-get' => '[Стример] Получить список потоков',
            'streamer-store-post' => '[Стример] Добавить поток',
            'streamer-update-put' => '[Стример] Обновить поток',
            'streamer-delete-delete' => '[Стример] Удалить поток',

            'inbox-index-get' => '[Сообщения] Получить список',
            'inbox-store-post' => '[Сообщения] Отправить сообщение пользователю',

            'key-index-get' => '[Ключи] Получить список',

            'sip-user-index-get' => '[SipUser] Получить список',
            'sip-user-store-post' => '[SipUser] Добавить пользователя',
            'sip-user-update-put' => '[SipUser] Обновить пользователя',
            'sip-user-delete-delete' => '[SipUser] Удалить пользователя',

            'account-audit-index-get' => '[Аудит] Получить список действий',

            'device-relay-index-get' => '[Устройство-Реле] Получить список устройств реле',
            'device-relay-show-get' => '[Устройство-Реле] Получить устройство реле',
            'device-relay-store-post' => '[Устройство-Реле] Добавить устройство реле',
            'device-relay-update-put' => '[Устройство-Реле] Обновить устройство реле',
            'device-relay-delete-delete' => '[Устройство-Реле] Удалить устройством реле',

            'device-relay-setting-index-get' => '[Устройство-Реле] Получить настройки реле',
            'device-relay-setting-update-put' => '[Устройство-Реле] Обновить настройки реле',

            'intercom-config-audio' => '[Домофон-Конфигурация] Управление аудио домофона',
            'intercom-config-show-get' => '[Домофон-Конфигурация] Получить настройку домофона',
            'intercom-config-update-put' => '[Домофон-Конфигурация] Обновить настройку домофона',

            'intercom-key-show-get' => '[Домофое-Ключи] Получить ключи с домофона',

            'subscriber-index-get' => '[Абонент] Получить список абонентов',
            'subscriber-show-get' => '[Абонент] Получить абонента',

            'subscriber-camera-index-get' => '[Абонент-Камера] Получить камеры абонента',
            'subscriber-camera-store-post' => '[Абонент-Камера] Привязать камеру к абоненту',
            'subscriber-camera-delete-post' => '[Абонент-Камера] Отвязать камеру от абонента',

            'group-index-get' => '[Группа] Получить список групп',
            'group-show-get' => '[Группа] Получить группу',
            'group-store-post' => '[Группа] Создать новую группу',
            'group-update-put' => '[Группа] Обновить группу',
            'group-delete-delete' => '[Группа] Удалить группу',

            'contractor-index-get' => '[Подрядчик] Получить список подрядчиков',
            'contractor-show-get' => '[Подрядчик] Получить подрядчика',
            'contractor-sync-get' => '[Подрядчик] Синхронизация подрядчика',
            'contractor-store-post' => '[Подрядчик] Создать нового подрядчика',
            'contractor-update-put' => '[Подрядчик] Обновить подрядчика',
            'contractor-delete-delete' => '[Подрядчик] Удалить подрядчика',

            'house-flat-camera-index-get' => '[Квартира-Камера] Получить список камер',
            'house-flat-camera-store-post' => '[Квартира-Камера] Привязать камеру к квартире',
            'house-flat-camera-delete-delete' => '[Квартира-Камера] Отвязать камеру от квартиры',

            'house-flat-key-index-get' => '[Квартира-Ключ] Получить список ключей'
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