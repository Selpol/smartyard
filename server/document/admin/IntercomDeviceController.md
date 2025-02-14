# Контроллер IntercomDeviceController `/admin/intercom/device`

Домофон-Устройство

## Методы

### [GET/info `intercom-device-info-get`] Получить информацию об домофоне `/admin/intercom/device/{id}`

Параметры: 

- `int` *id* Идентификатор домофона

### [POST/call `intercom-device-call-post`] Позвонить с домофона или сбросить звонок `/admin/intercom/device/call/{id}`

Параметры: 

- [IntercomDeviceCallRequest](../OBJECT.md#IntercomDeviceCallRequest) 

### [POST/level `intercom-device-level-post`] Получить уровни с домофона `/admin/intercom/device/level/{id}`

Параметры: 

- [IntercomDeviceLevelRequest](../OBJECT.md#IntercomDeviceLevelRequest) 

### [POST/open `intercom-device-open-post`] Открыть реле домофона `/admin/intercom/device/open/{id}`

Параметры: 

- [IntercomDeviceOpenRequest](../OBJECT.md#IntercomDeviceOpenRequest) 

### [POST/password `intercom-device-password-post`] Обновить пароль на домофоне `/admin/intercom/device/password/{id}`

Параметры: 

- [IntercomDevicePasswordRequest](../OBJECT.md#IntercomDevicePasswordRequest) 

### [POST/reboot `intercom-device-reboot-post`] Перезапустить домофон `/admin/intercom/device/reboot/{id}`

Параметры: 

- `int` *id*

### [POST/reset `intercom-device-reset-post`] Сброс домофона `/admin/intercom/device/reset/{id}`

Параметры: 

- [IntercomDeviceResetRequest](../OBJECT.md#IntercomDeviceResetRequest) 

### [POST/sync `intercom-device-sync-post`] Синхронизация домофона `/admin/intercom/device/sync/{id}`

Параметры: 

- `int` *id* Идентификатор домофона
