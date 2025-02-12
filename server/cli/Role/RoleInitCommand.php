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
            'authentication-index-get' => '[Авторизация] Получить перечень прав пользователя',
            'authentication-store-post' => '[Авторизация] Авторизация из-под пользователя',
            'authentication-update-put' => '[Авторизация] Выход из-под пользователя',

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

            'key-index-get' => '[Ключ] Получить список ключей',
            'key-show-get' => '[Ключ] Получить ключ',
            'key-store-post' => '[Ключ] Добавить ключ',
            'key-update-put' => '[Ключ] Обновить ключ',
            'key-delete-delete' => '[Ключ] Удалить ключ ',

            'sip-user-index-get' => '[SipUser] Получить список',
            'sip-user-store-post' => '[SipUser] Добавить пользователя',
            'sip-user-update-put' => '[SipUser] Обновить пользователя',
            'sip-user-delete-delete' => '[SipUser] Удалить пользователя',

            'account-audit-index-get' => '[Аудит] Получить список действий',
            'account-audit-description-get' => '[Аудит] Получить описание',

            'intercom-config-audio' => '[Домофон-Конфигурация] Управление аудио домофона',
            'intercom-config-show-get' => '[Домофон-Конфигурация] Получить настройку домофона',
            'intercom-config-update-put' => '[Домофон-Конфигурация] Обновить настройку домофона',

            'intercom-key-show-get' => '[Домофое-Ключи] Получить ключи с домофона',
            'intercom-log-index-get' => '[Домофон-Логи] Получить логи с домофона',

            'subscriber-index-get' => '[Абонент] Получить список абонентов',
            'subscriber-show-get' => '[Абонент] Получить абонента',
            'subscriber-store-post' => '[Абонент] Создать нового абонента',
            'subscriber-update-put' => '[Абонент] Обновить абонента',
            'subscriber-delete-delete' => '[Абонент] Удалить абонента',

            'subscriber-camera-index-get' => '[Абонент-Камера] Получить камеры абонента',
            'subscriber-camera-store-post' => '[Абонент-Камера] Привязать камеру к абоненту',
            'subscriber-camera-delete-post' => '[Абонент-Камера] Отвязать камеру от абонента',

            'subscriber-flat-index-get' => '[Абонент-Квартира] Получить квартиры абонента',
            'subscriber-flat-store-post' => '[Абонент-Квартира] Привязать квартиру к абоненту',
            'subscriber-flat-update-put' => '[Абонент-Квартира] Обновить привязку абонента к квартире',
            'subscriber-flat-delete-delete' => '[Абонент-Квартира] Отвязать квартиру от абонента ',

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

            'house-camera-index-get' => '[Дом-Камера] Получить список камер',
            'house-camera-store-post' => '[Дом-Камера] Привязать камеру к дому',
            'house-camera-delete-delete' => '[Дом-Камера] Отвязать камеру от дома',

            'house-flat-camera-index-get' => '[Квартира-Камера] Получить список камер',
            'house-flat-camera-store-post' => '[Квартира-Камера] Привязать камеру к квартире',
            'house-flat-camera-delete-delete' => '[Квартира-Камера] Отвязать камеру от квартиры',

            'house-flat-key-index-get' => '[Квартира-Ключ] Получить список ключей',

            'permission-index-get' => '[Права] Получить список прав',
            'permission-update-put' => '[Права ] Обновить право',

            'role-index-get' => '[Роль] Получить список прав',
            'role-store-post' => '[Роль] Создать новую роль',
            'role-update-put' => '[Роль] Обновить роль',
            'role-delete-delete' => '[Роль] Удалить роль',

            'role-permission-index-get' => '[Роль-Права] Получить список прав роли',
            'role-permission-store-post' => '[Роль-Права] Привязать право к роли',
            'role-permission-delete-delete' => '[Роль-Права] Отвязать право от роли',

            'user-index-get' => '[Пользователь] Получить список пользователей',
            'user-show-get' => '[Пользователь] Получить пользователя',
            'user-store-post' => '[Пользователь] Создать нового пользователя',
            'user-update-put' => '[Пользователь]  Обновить пользователя',
            'user-delete-delete' => '[Пользователь] Удалить пользователя',

            'user-session-show-get' => '[Пользователь-Сессия] Получить список сессий пользователя',
            'user-session-update-put' => '[Пользователь-Сессия] Отключить сессию пользователя',

            'user-setting-index-get' => '[Пользователь-Настройки] Получить настройки пользователя',

            'user-permission-index-get' => '[Пользователь-Права] Получить список прав пользователя',
            'user-permission-store-post' => '[Пользователь-Права] Привязать право к пользователю',
            'user-permission-delete-delete' => '[Пользователь-Права] Отвязать право от пользователя',

            'user-role-index-get' => '[Пользователь-Роль] Получить список ролей пользователя',
            'user-role-store-post' => '[Пользователь-Роль] Привязать роль к пользователю',
            'user-role-delete-delete' => '[Пользователь-Роль] Отвязать роль от пользователя',

            'server-variable-index-get' => '[Сервер-Переменная] Получить список переменных',
            'server-variable-update-put' => '[Сервер-Переменная] Обновить переменную',

            'server-streamer-index-get' => '[Сервер-Стреамер] Получить список стримеров',
            'server-streamer-store-post' => '[Сервер-Стреамер] Создание нового стримера',
            'server-streamer-update-put' => '[Сервер-Стреамер] Обновление стримера',
            'server-streamer-delete-delete' => '[Сервер-Стреамер] Удалить стример',

            'server-sip-index-get' => '[Сервер-Сип] Получить список сип',
            'server-sip-store-post' => '[Сервер-Сип] Создание новый сип',
            'server-sip-update-put' => '[Сервер-Сип] Обновление сип',
            'server-sip-delete-delete' => '[Сервер-Сип] Удалить сип',

            'server-frs-index-get' => '[Сервер-Лиц] Получить список серверов лиц',
            'server-frs-store-post' => '[Сервер-Лиц] Создание нового сервера лиц',
            'server-frs-update-put' => '[Сервер-Лиц] Обновление сервера лиц',
            'server-frs-delete-delete' => '[Сервер-Лиц] Удалить сервера лиц',

            'server-dvr-index-get' => '[Сервер-Архивов] Получить список серверов архивов',
            'server-dvr-store-post' => '[Сервер-Архивов] Создание нового сервера архива',
            'server-dvr-update-put' => '[Сервер-Архивов] Обновление сервера архива',
            'server-dvr-delete-delete' => '[Сервер-Архивов] Удалить сервера архива',

            'camera-index-get' => '[Камера] Получить список камер',
            'camera-show-get' => '[Камера] Получить камеру',
            'camera-screenshot-get' => '[Камера] Получить скриншот с камеры',
            'camera-store-post' => '[Камера] Создать новую камеру',
            'camera-update-put' => '[Камера] Обновить камеру',
            'camera-delete-delete' => '[Камера] Удалить камеру',

            'camera-model-index-get' => '[Камера-Модель] Получить список моделей камер'
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