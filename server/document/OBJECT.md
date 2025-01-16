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

## ConfigIndexRequest

Поля: 

- `string` *type* - Тип подсказки для конфигурации

## ConfigOptimizeRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `bool` *optimize* - Оптимизация конфигурации

## DeviceRelayIndexRequest

Поля: 

- `int` *page* - Страница
- `int` *size* - Размер страницы

## DeviceRelayStoreRequest

Поля: 

- `string` *title* - Название устройства
- `string` *url* - Ссылка на устройство
- `string` *credential* - Авторизация для устройства

## DeviceRelayUpdateRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `null|string` *title* - Название устройства
- `null|string` *url* - Ссылка на устройство
- `null|string` *credential* - Авторизация для устройства

## DeviceRelaySettingUpdateRequest

Поля: 

- `int` *id* - Идентификатор устройства
- `null|string` *authentication* - Авторизация в формате Base64
- `null|int` *open_duration* - Время октрытия реле
- `null|string` *ping_address* - Ip-адрес для пинга
- `null|int` *ping_timeout* - Таймаут пинга

## DvrShowRequest

Поля: 

- `int` *id* - Идентификатор DVR сервера
- `string` *search* - Строка поиска камеры

## GeoIndexRequest

Поля: 

- `string` *search* - Строка поиска
- `null|string` *bound* - Ограничение поиска

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

## KeyIndexRequest

Поля: 

- `null|string` *rfid* - RFID-Метка
- `null|string` *comments* - Комментарий
- `int` *page* - Страница
- `int` *size* - Размер страницы

## LogIndexRequest

Поля: 

- `null|string` *file* - Путь к файлу логов

## MonitorIntercomRequest

Поля: 

- `null|string` *type* - Тип мониторинга
- `string` *device* - Тип устройства, используется только для ping
- `int[]` *ids* - Список идентификаторов устройств

## PlogIndexRequest

Поля: 

- `int` *id* - Идентификатор квартиры
- `null|int` *type* - Тип события
- `null|bool` *opened* - Было ли открытие во время звонка
- `int` *page* - Страница
- `int` *size* - Размер страницы

## PlogCamshotRequest

Поля: 

- `string` *uuid* - Идентификатор картинки

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

## SubscriberRequest

Поля: 

- `null|int[]` *ids* - Идентификаторы абонентов
- `int|null` *flat_id* - Идентификатор квартиры
- `null|string` *name* - Имя
- `null|string` *patronymic* - Отчество
- `null|string` *mobile* - Номер телефона
- `int` *page* - Страница
- `int` *size* - Размер страницы

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
- `bool|null` *sub*
- `bool|null` *hw*

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
