# Контроллер DeviceRelayController `/admin/device/relay`

Устройство-реле

## Методы

### [GET/index `device-relay-index-get`] Получить список устройств реле `/admin/device/relay`

Параметры: 

- [DeviceRelayIndexRequest](../OBJECT.md#DeviceRelayIndexRequest) 

### [GET/show `device-relay-show-get`] Получить устройство реле `/admin/device/relay/{id}`

Параметры: 

- `int` *id* Идентификатор устройства

### [POST/store `device-relay-store-post`] Добавить устройство реле `/admin/device/relay`

Параметры: 

- [DeviceRelayStoreRequest](../OBJECT.md#DeviceRelayStoreRequest) 

### [PUT/update `device-relay-update-put`] Обновить устройство реле `/admin/device/relay/{id}`

Параметры: 

- [DeviceRelayUpdateRequest](../OBJECT.md#DeviceRelayUpdateRequest) 

### [GET/flap `device-relay-flap-get`] Флапнуть устройством реле `/admin/device/relay/flap/{id}`

Параметры: 

- [DeviceRelayFlapRequest](../OBJECT.md#DeviceRelayFlapRequest) 

### [DELETE/delete `device-relay-delete-delete`] Удалить устройство реле `/admin/device/relay/{id}`

Параметры: 

- `int` *id*
