# Объекты

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

## DvrShowRequest

Поля: 

- `int` *id* - Идентификатор DVR сервера
- `string` *search* - Строка поиска камеры

## GeoIndexRequest

Поля: 

- `string` *search* - Строка поиска
- `null|string` *bound* - Ограничение поиска

## LogIndexRequest

Поля: 

- `null|string` *file* - Путь к файлу логов

## MonitorIntercomRequest

Поля: 

- `null|string` *type* - Тип мониторинга
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

- `int` *date;*
- `string` *ip*
- `null|int` *callId*

## ActionMotionDetectionRequest

Поля: 

- `string` *ip*
- `bool` *motionActive*

## ActionOpenDoorRequest

Поля: 

- `int` *date*
- `string` *ip*
- `int` *event*
- `int` *door*
- `string` *detail*

## ActionSetRabbitGatesRequest

Поля: 

- `string` *ip*
- `int` *prefix*
- `int` *apartmentNumber*
- `int` *apartmentId*
- `int` *date*

## FrsCallbackRequest

Поля: 

- `int` *stream_id*
- `int` *eventId*
- `int` *faceId*

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
