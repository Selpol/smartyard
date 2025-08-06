# Объекты

## AccountAuditIndexRequest

Поля: 

- `null|int` *user_id* - Идентификатор пользователя
- `null|string` *auditable_id* - Идентификатор сущности
- `null|string` *auditable_type* - Тип сущности
- `null|string` *event_ip* - IP-адрес с которого произошел аудит
- `null|string` *event_type* - Тип аудита
- `null|string` *event_target* - Адрес аудита
- `null|string` *event_code* - Код аудита
- `null|string` *event_message* - Сообщение аудита
- `int` *page* - Страница
- `int` *size* - Размер страницы

## AddressAreaIndexRequest

Поля: 

- `int|null` *address_region_id*

## AddressAreaStoreRequest

Поля: 

- `int` *address_region_id*
- `string|null` *area_uuid*
- `string` *area_with_type*
- `string|null` *area_type*
- `string|null` *area_type_full*
- `string` *area*
- `string|null` *timezone*

## AddressAreaUpdateRequest

Поля: 

- `int` *id*
- `int` *address_region_id*
- `string|null` *area_uuid*
- `string` *area_with_type*
- `string|null` *area_type*
- `string|null` *area_type_full*
- `string` *area*
- `string|null` *timezone*

## AddressCityIndexRequest

Поля: 

- `int|null` *address_region_id*
- `int|null` *address_area_id*

## AddressCityStoreRequest

Поля: 

- `int|null` *address_region_id*
- `int|null` *address_area_id*
- `string|null` *city_uuid*
- `string` *city_with_type*
- `string|null` *city_type*
- `string|null` *city_type_full*
- `string` *city*
- `string|null` *timezone*

## AddressCityUpdateRequest

Поля: 

- `int` *id*
- `int|null` *address_region_id*
- `int|null` *address_area_id*
- `string|null` *city_uuid*
- `string` *city_with_type*
- `string|null` *city_type*
- `string|null` *city_type_full*
- `string` *city*
- `string|null` *timezone*

## AddressHouseIndexRequest

Поля: 

- `null|int[]` *ids* - Идентификаторы домов
- `null|string` *house_full* - Полный адрес дома
- `int|null` *address_settlement_id*
- `int|null` *address_street_id*

## AddressHouseQrRequest

Поля: 

- `int` *id* - Идентификатор дома
- `bool` *override* - Перегенерировать коды

## AddressHouseStoreRequest

Поля: 

- `int|null` *address_settlement_id*
- `int|null` *address_street_id*
- `string|null` *house_uuid*
- `string` *house_type*
- `string|null` *house_type_full*
- `string|null` *house_full*
- `string` *house*
- `string|null` *timezone*

## AddressHouseMagicRequest

Поля: 

- `string` *address*

## AddressHouseUpdateRequest

Поля: 

- `int` *id*
- `int|null` *address_settlement_id*
- `int|null` *address_street_id*
- `string|null` *house_uuid*
- `string` *house_type*
- `string|null` *house_type_full*
- `string|null` *house_full*
- `string` *house*
- `string|null` *timezone*

## PageRequest

Поля: 

- `int` *page* - Страница
- `int` *size* - Размер страницы

## AddressRegionStoreRequest

Поля: 

- `string|null` *region_uuid*
- `string|null` *region_iso_code*
- `string` *region_with_type*
- `string|null` *region_type*
- `string|null` *region_type_full*
- `string` *region*
- `string|null` *timezone*

## AddressRegionUpdateRequest

Поля: 

- `int` *id*
- `string|null` *region_uuid*
- `string|null` *region_iso_code*
- `string` *region_with_type*
- `string|null` *region_type*
- `string|null` *region_type_full*
- `string` *region*
- `string|null` *timezone*

## AddressSettlementIndexRequest

Поля: 

- `int|null` *address_area_id*
- `int|null` *address_city_id*

## AddressSettlementStoreRequest

Поля: 

- `int|null` *address_area_id*
- `int|null` *address_city_id*
- `string|null` *settlement_uuid*
- `string` *settlement_with_type*
- `string|null` *settlement_type*
- `string|null` *settlement_type_full*
- `string` *settlement*
- `string|null` *timezone*

## AddressSettlementUpdateRequest

Поля: 

- `int` *id*
- `int|null` *address_area_id*
- `int|null` *address_city_id*
- `string|null` *settlement_uuid*
- `string` *settlement_with_type*
- `string|null` *settlement_type*
- `string|null` *settlement_type_full*
- `string` *settlement*
- `string|null` *timezone*

## AddressStreetIndexRequest

Поля: 

- `int|null` *address_city_id*
- `int|null` *address_settlement_id*

## AddressStreetStoreRequest

Поля: 

- `int|null` *address_city_id*
- `int|null` *address_settlement_id*
- `string|null` *street_uuid*
- `string` *street_with_type*
- `string|null` *street_type*
- `string|null` *street_type_full*
- `string` *street*
- `string|null` *timezone*

## AddressStreetUpdateRequest

Поля: 

- `int` *id*
- `int|null` *address_city_id*
- `int|null` *address_settlement_id*
- `string|null` *street_uuid*
- `string` *street_with_type*
- `string|null` *street_type*
- `string|null` *street_type_full*
- `string` *street*
- `string|null` *timezone*

## AuthenticationRequest

Поля: 

- `string` *login* - Логин
- `string` *password* - Пароль
- `bool` *remember_me* - Запомнить вход, как уникальный
- `string|null` *user_agent* - User-Agent пользователя
- `string|null` *did* - Уникальный идентификатор

## BlockFlatStoreRequest

Поля: 

- `int` *flat_id* - Идентификатор квартиры
- `null|bool` *notify* - Уведомить абонентов
- `int` *service* - Служба для блокировки
- `null|string` *cause* - Официальная причина
- `null|string` *comment* - Комментарий

## BlockUpdateRequest

Поля: 

- `int` *id* - Идентификатор блокировки
- `null|bool` *notify* - Уведомить абонентов
- `null|string` *cause* - Официальная причина
- `null|string` *comment* - Комментарий

## BlockDeleteRequest

Поля: 

- `int` *id* - Идентификатор блокировки
- `null|bool` *notify* - Уведомить абонентов

## BlockSubscriberStoreRequest

Поля: 

- `int` *subscriber_id* - Идентификатор абонента
- `null|bool` *notify* - Уведомить абонентов
- `int` *service* - Служба для блокировки
- `null|string` *cause* - Официальная причина
- `null|string` *comment* - Комментарий

## CameraIndexRequest

Поля: 

- `string|null` *comment* - Комментарий
- `string|null` *model* - Модель камеры
- `string|null` *ip* - IP камеры
- `string|null` *device_id*
- `string|null` *device_model*
- `string|null` *device_software_version*
- `string|null` *device_hardware_version*

## CameraStoreRequest

Поля: 

- `int|null` *dvr_server_id* - Идентификатор сервера архива
- `int|null` *frs_server_id* - Идентификатор сервера лиц
- `int` *enabled* - Статус камеры
- `string` *model* - Модель камеры
- `string` *url* - URL Камеры
- `string|null` *stream*
- `string` *credentials* - Авторизация камеры
- `string|null` *name* - Имя камеры
- `string|null` *dvr_stream* - Идентификатор стрима на сервере ахрива
- `string|null` *timezone* - Временная зона камеры
- `double|null` *lat* - Позиция камеры
- `double|null` *lon* - Позиция камеры
- `int|null` *common*
- `string|null` *ip*
- `string|null` *comment* - Комментарий камеры
- `string|null` *config* - Конфигурация камеры
- `bool` *hidden* - Скрытая ли камера

## CameraUpdateRequest

Поля: 

- `int` *id* - Идентификатор камеры
- `int|null` *dvr_server_id* - Идентификатор сервера архива
- `int|null` *frs_server_id* - Идентификатор сервера лиц
- `int` *enabled* - Статус камеры
- `string` *model* - Модель камеры
- `string` *url* - URL Камеры
- `string|null` *stream*
- `string` *credentials* - Авторизация камеры
- `string|null` *name* - Имя камеры
- `string|null` *dvr_stream* - Идентификатор стрима на сервере ахрива
- `string|null` *timezone* - Временная зона камеры
- `double|null` *lat* - Позиция камеры
- `double|null` *lon* - Позиция камеры
- `int|null` *common*
- `string|null` *ip*
- `string|null` *comment* - Комментарий камеры
- `string|null` *config* - Конфигурация камеры
- `bool` *hidden* - Скрытая ли камера

## ConfigIndexRequest

Поля: 

- `string` *type* - Тип подсказки для конфигурации

## ConfigOptimizeRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `bool` *optimize* - Оптимизация конфигурации

## ContractIndexRequest

Поля: 

- `string|null` *title* - Название
- `int|null` *flat* - Квартира
- `int` *page* - Страница
- `int` *size* - Размер страницы

## ContractSyncRequest

Поля: 

- `int` *id* - Идентификатор подрядчика
- `bool` *remove_subscriber* - Удалять ли абонентов
- `bool` *remove_key* - Удалять ли ключи

## ContractStoreRequest

Поля: 

- `string` *title* - Название
- `int` *flat* - Квартира
- `int` *flat_flag* - Флаги квартиры
- `string|null` *code* - Код открытия

## ContractUpdateRequest

Поля: 

- `int` *id* - Идентификатор подрядчика
- `string` *title* - Название
- `int` *flat* - Квартира
- `int` *flat_flag* - Флаги квартиры
- `string|null` *code* - Код открытия

## DvrShowRequest

Поля: 

- `int` *id* - Идентификатор DVR сервера
- `string` *camera* - Идентификатор камеры

## EntranceCmsRequest

Поля: 

- `int` *id* - Идентификатор входа
- `array` *cmses* - Массив КМС входа

## EntranceFlatRequest

Поля: 

- `int` *id* - Идентификатор входа
- `array` *flats* - Квартиры

## GeoIndexRequest

Поля: 

- `string` *search* - Строка поиска
- `null|string` *bound* - Ограничение поиска

## GroupIndexRequest

Поля: 

- `string|null` *name* - Название
- `string|null` *type* - Тип абонент, камера, домофон, ключ, адрес
- `string|null` *for* - Сущность подрядчик или адрес
- `string|null` *id* - Идентификатор сущности
- `int` *page* - Страница
- `int` *size* - Размер страницы

## GroupStoreRequest

Поля: 

- `string` *name* - Название
- `string` *type* - Тип абонент, камера, домофон, ключ, адрес
- `string` *for* - Сущность подрядчик или адрес
- `int` *id* - Идентификатор сущности
- `mixed` *value* - Значение

## GroupUpdateRequest

Поля: 

- `string` *oid* - Идентификатор группы
- `string` *name* - Название
- `string` *type* - Тип абонент, камера, домофон, ключ, адрес
- `string` *for* - Сущность подрядчик или адрес
- `int` *id* - Идентификатор сущности
- `mixed` *value* - Значение

## HouseKeyRequest

Поля: 

- `int` *id* - Идентификатор дома
- `array` *keys* - Список ключей {rfId, accessTo, comment?}[]

## InboxIndexRequest

Поля: 

- `int` *id* - Идентификатор абонента
- `string|null` *message_id* - Идентификатор сообщения
- `int|null` *from* - Дата начала
- `int|null` *to* - Дата окончания

## InboxStoreRequest

Поля: 

- `int` *id* - Идентификатор абонента
- `string` *title* - Заголовок
- `string` *body* - Описание
- `string` *action* - Тип действия

## IntercomConfigShowRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `string` *key* - Ключ значения

## IntercomConfigUpdateRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `string` *key* - Ключ
- `string` *value* - Значение

## IntercomIndexRequest

Поля: 

- `string|null` *comment* - Комментарий
- `string|null` *model* - Модель домофона
- `string|null` *ip* - IP домофона
- `string|null` *device_id*
- `string|null` *device_model*
- `string|null` *device_software_version*
- `string|null` *device_hardware_version*

## IntercomStoreRequest

Поля: 

- `string` *model* - Модель домофона
- `string` *server* - Сервер
- `string` *url* - URL Домофона
- `string` *credentials* - Авторизация
- `string|null` *ip* - IP домофона
- `string|null` *comment* - Комментарий
- `string|null` *config* - Конфигурация домофона
- `bool|null` *hidden* - Скрытый домофон

## IntercomUpdateRequest

Поля: 

- `int` *id* - Идентификатор домофона
- `string` *model* - Модель домофона
- `string` *server* - Сервер
- `string` *url* - URL Домофона
- `string` *credentials* - Авторизация
- `int` *first_time* - Первая синхронизация
- `string|null` *ip* - IP домофона
- `string|null` *comment* - Комментарий
- `string|null` *config* - Конфигурация домофона
- `bool|null` *hidden* - Скрытый домофон

## IntercomDeviceCallRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `int|null` *apartment* - Квартира

## IntercomDeviceLevelRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `int|null` *apartment* - Квартира
- `int|null` *from* - Первая квартира
- `int|null` *to* - Последняя квартира
- `bool` *info* - Дополнительная информация

## IntercomDeviceOpenRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `int` *output* - Номер реле

## IntercomDevicePasswordRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `string|null` *password* - Пароль

## IntercomDeviceResetRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `string` *type* - Тип действия

## IntercomLogIndexRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `int|null` *min_date* - Минимальная дата
- `int|null` *max_date* - Максимальная дата
- `string|null` *message* - Сообщение лога
- `int` *page* - Страница
- `int` *size* - Размер страницы

## KeyIndexRequest

Поля: 

- `null|string` *rfid* - RFID-Метка
- `null|string` *comments* - Комментарий
- `int` *page* - Страница
- `int` *size* - Размер страницы

## KeyStoreRequest

Поля: 

- `string` *rfid* - RFID-Метка
- `int` *access_type* - Тип доступа 2 - квартира
- `int` *access_to* - Куда доступ
- `string|null` *comments* - Комментарий

## KeyUpdateRequest

Поля: 

- `int` *id* - Идентификатор ключа
- `null|string` *comments* - Комментарий

## MonitorIntercomRequest

Поля: 

- `null|string` *type* - Тип мониторинга
- `string` *device* - Тип устройства, используется только для ping
- `int[]` *ids* - Список идентификаторов устройств

## PermissionUpdateRequest

Поля: 

- `int` *id* - Идентификатор права
- `string` *description* - Описание права

## PlogIndexRequest

Поля: 

- `int` *id* - Идентификатор
- `null|int` *type* - Тип события
- `null|bool` *opened* - Было ли открытие во время звонка
- `int` *page* - Страница
- `int` *size* - Размер страницы

## PlogCamshotRequest

Поля: 

- `string` *uuid* - Идентификатор картинки

## RoleStoreRequest

Поля: 

- `string` *title* - Заголовок
- `string` *description* - Описание

## RoleUpdateRequest

Поля: 

- `int` *id* - Идентификатор роли
- `string` *title* - Заголовок
- `string` *description* - Описание

## ScheduleIndexRequest

Поля: 

- `null|string` *title* - Заголовок
- `null|int` *status* - Статус
- `int` *page* - Страница
- `int` *size* - Размер страницы

## ScheduleStoreRequest

Поля: 

- `string` *title* - Заголовок
- `string` *time* - Время
- `string` *script* - Скрипт
- `int` *status* - Статус

## ScheduleUpdateRequest

Поля: 

- `int` *id* - Идентификатор расписания
- `string` *title* - Заголовок
- `string` *time* - Время
- `string` *script* - Скрипт
- `int` *status* - Статус

## ServerDvrStoreRequest

Поля: 

- `string` *title* - Название сервера архива
- `string` *type* - Тип сервера архива. Поддерживается: flunnonic, trassir
- `string` *url* - URL к серверу ахрива
- `string` *token* - Токен для доступа к архиву
- `string` *credentials* - Авторизация на сервере архива

## ServerDvrUpdateRequest

Поля: 

- `int` *id* - Идентификатор сервера архива
- `string` *title* - Название сервера архива
- `string` *type* - Тип сервера архива. Поддерживается: flunnonic, trassir
- `string` *url* - URL к серверу ахрива
- `string` *token* - Токен для доступа к архиву
- `string|null` *credentials* - Авторизация на сервере архива

## ServerStreamerFrsStoreRequest

Поля: 

- `string` *title* - Название стримера
- `string` *url* - URL к стримеру

## ServerStreamerFrsUpdateRequest

Поля: 

- `int` *id* - Идентификатор стримера
- `string` *title* - Название стримера
- `string` *url* - URL к стримеру

## ServerSipStoreRequest

Поля: 

- `string` *title* - Название сипа
- `string` *type* - Тип сипа. Поддерживается: asterisk
- `string` *trunk* - Транк
- `string` *external_ip* - IP для абонентов
- `string` *internal_ip* - IP для домофонов
- `int` *external_port* - Порт для абонентов
- `int` *internal_port* - Порт для домофонов

## ServerSipUpdateRequest

Поля: 

- `int` *id* - Идентификатор сипа
- `string` *title* - Название сипа
- `string` *type* - Тип сипа. Поддерживается: asterisk
- `string` *trunk* - Транк
- `string` *external_ip* - IP для абонентов
- `string` *internal_ip* - IP для домофонов
- `int` *external_port* - Порт для абонентов
- `int` *internal_port* - Порт для домофонов

## ServerVariableUpdateRequest

Поля: 

- `int` *var_id* - Идентификатор переменной
- `string` *var_value* - Значение переменной

## SipUserIndexRequest

Поля: 

- `null|int` *type* - Префикс номера
- `null|string` *title* - Имя аккаунта
- `int` *page* - Страница
- `int` *size* - Размер страницы

## SipUserStoreRequest

Поля: 

- `int` *type* - Префикс номера
- `string` *title* - Имя аккаунта
- `string` *password* - Пароль аккаунта

## SipUserUpdateRequest

Поля: 

- `int` *id* - Идентификатор аккаунтп
- `int` *type* - Префикс номера
- `string` *title* - Имя аккаунта
- `string` *password* - Пароль аккаунта

## StreamerRequest

Поля: 

- `int` *id* - Идентификатор стримера
- `string` *stream_id* - Идентификатор потока
- `string` *input* - Входящий поток
- `string` *input_type* - Тип входящего потока
- `string` *output_type* - Тип выходящего потока

## StreamerDeleteRequest

Поля: 

- `int` *id* - Идентификатор стримера
- `string` *stream_id* - Идентификатор потока

## SubscriberCameraRequest

Поля: 

- `int` *house_subscriber_id* - Идентификатор абонента
- `int` *camera_id* - Идентификатор камеры

## SubscriberRequest

Поля: 

- `null|int[]` *ids* - Идентификаторы абонентов
- `int|null` *flat_id* - Идентификатор квартиры
- `null|string` *name* - Имя
- `null|string` *patronymic* - Отчество
- `null|string` *mobile* - Номер телефона
- `null|string` *platform* - Платформа. 0 - ANDROID, 1 - IOS, 2 - WEB
- `null|string` *push_token_type* - Тип токена. 0 - GMS, 1 - IOS PROD, 2 - IOS DEV, 3 - HMS, 4 - RSR
- `int` *page* - Страница
- `int` *size* - Размер страницы

## SubscriberStoreRequest

Поля: 

- `string` *id* - Номер телефона
- `string` *subscriber_name* - Имя абонента
- `string` *subscriber_patronymic* - Отчество клиента

## SubscriberUpdateRequest

Поля: 

- `int` *house_subscriber_id* - Идентификатор абонента
- `string` *subscriber_name* - Имя абонента
- `string` *subscriber_patronymic* - Отчество клиента
- `int` *voip_enabled* - VoIp звонок для IOS

## SubscriberFlatRequest

Поля: 

- `int` *house_subscriber_id* - Идентификатор абонента
- `int` *flat_id* - Идентификатор квартиры
- `int` *role* - Роль абонента в квартире, 0 - Владелец, 1 - Жилец
- `int` *call* - Статус звонков, 0 - Выключены, 1 - Включено

## TaskSearchRequest

Поля: 

- `string|null` *title* - Заголовок задачи
- `string|null` *message* - Сообщение завершения задачи
- `class-string|null` *class* - Обработчик задачи
- `int` *page* - Страница
- `int` *size* - Размер страницы

## TaskDeleteRequest

Поля: 

- `string` *key* - Ключ для удаления

## UserStoreRequest

Поля: 

- `string` *login* - Логин
- `string` *password* - Пароль
- `string` *name* - Имя
- `string|null` *phone* - Номер телефона
- `int` *enabled* - Включен ли пользователь

## UserUpdateRequest

Поля: 

- `int` *id* - Идентификатор пользователя
- `string|null` *login* - Логин
- `string|null` *password* - Пароль
- `string|null` *name* - Имя
- `string|null` *phone* - Номер телефона
- `int` *enabled* - Включен ли пользователь

## ActionCallFinishedRequest

Поля: 

- `int` *date* - Дата события
- `string` *ip* - IP-Адрес устройства
- `null|int` *callId* - Номер звонящего

## ActionMotionDetectionRequest

Поля: 

- `string` *ip* - IP-адрес устройства
- `bool` *motionActive* - Статус детекции

## ActionOpenDoorRequest

Поля: 

- `int` *date* - Дата события
- `string` *ip* - IP-адрес устройства
- `int` *event* - Тип события
- `int` *door* - Номер входа на устройстве
- `string` *detail* - Дополнительные детали события

## ActionSetRabbitGatesRequest

Поля: 

- `string` *ip* - IP-адрес устройства
- `int` *prefix* - Префикс устройства
- `int` *apartmentNumber* - Номер квартиры
- `int` *apartmentId* - Идентификатор квартиры
- `int` *date* - Дата события

## DhcpRequest

Поля: 

- `string|null` *ip* - IP-Адрес устройства
- `string|null` *mac* - MAC-Адрес устройства
- `string|null` *host* - Hostname
- `string|null` *server* - DHCP Сервер

## FrsCallbackRequest

Поля: 

- `int` *stream_id* - Идентификатор потока
- `int` *eventId* - Идентификатор события
- `int` *faceId* - Идентификатор лица

## AddressRegisterQrRequest

Поля: 

- `string` *QR*
- `string|int` *mobile*
- `string|null` *name*
- `string|null` *patronymic*

## ArchivePrepareRequest

Поля: 

- `int` *id*
- `string` *from*
- `string` *to*

## CameraIndexRequest

Поля: 

- `int|null` *houseId*

## CameraGetRequest

Поля: 

- `int|null` *house_id*
- `int|null` *flat_id*
- `int|null` *entrance_id*

## CameraCommonDvrRequest

Поля: 

- `int` *id*

## CameraShowRequest

Поля: 

- `int` *houseId*
- `int` *id*

## CameraEventsRequest

Поля: 

- `int` *cameraId*
- `int` *date*

## DvrIdentifierRequest

Поля: 

- `int` *id*
- `int|null` *house_id*
- `int|null` *flat_id*
- `int|null` *entrance_id*
- `int|null` *time*

## DvrScreenshotRequest

Поля: 

- `string` *id*
- `int|null` *time*

## DvrPreviewRequest

Поля: 

- `string` *id*
- `int|null` *time*

## DvrVideoRequest

Поля: 

- `string` *id*
- `string` *container*
- `string` *stream*
- `int|null` *time*
- `array|null` *capabilities*

## DvrTimelineRequest

Поля: 

- `string` *id*
- `string|null` *token*

## DvrEventRequest

Поля: 

- `string` *id*
- `int` *after*
- `int` *before*
- `string|null` *token*

## DvrCommandRequest

Поля: 

- `string` *id*
- `string` *container*
- `string` *stream*
- `string` *command*
- `int|null` *seek*
- `int|null` *speed*
- `string|null` *token*
- `int|null` *from*
- `int|null` *to*

## FlatUpdateRequest

Поля: 

- `int` *id*
- `int` *call*

## FrsDeleteRequest

Поля: 

- `int` *eventId*
- `int|null` *flat_id*
- `int|null` *face_id*
- `int|null` *flatId*
- `int|null` *faceId*

## InboxIndexRequest

Поля: 

- `int|null` *date*
- `int` *page*
- `int` *size*

## InboxReadRequest

Поля: 

- `int|null` *messageId*

## PlogIndexRequest

Поля: 

- `int` *flatId*
- `string` *day*

## PlogDaysRequest

Поля: 

- `int` *flatId*
- `string` *events*

## SubscriberStoreRequest

Поля: 

- `int` *mobile*

## SubscriberDeleteRequest

Поля: 

- `int` *subscriberId*

## UserRegisterPushTokenRequest

Поля: 

- `string|null` *pushToken*
- `string|null` *voipToken*
- `bool` *production*
- `string` *platform*
- `bool` *voipEnabled*

## UserSendNameRequest

Поля: 

- `string` *name*
- `string|null` *patronymic*
