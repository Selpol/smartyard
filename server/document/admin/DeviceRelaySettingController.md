# Контроллер DeviceRelaySettingController `/admin/device/relay/setting`

Устройство-реле настройки

## Методы

### [GET/index `device-relay-setting-index-get`] Получить настройки устройства `/admin/device/relay/setting/{id}`

Параметры: 

- `int` *id* Идентификатор устройства

### [PUT/update `device-relay-setting-update-put`] Обновить настройки устройства `/admin/device/relay/setting/{id}`

Параметры: 

- [DeviceRelaySettingUpdateRequest](../OBJECT.md#DeviceRelaySettingUpdateRequest) 

### [GET/flap `device-relay-setting-flap-get`] Флапнуть устройством реле `/admin/device/relay/setting/flap/{id}`

Параметры: 

- [DeviceRelaySettingFlapRequest](../OBJECT.md#DeviceRelaySettingFlapRequest) 

### [GET/mode `device-relay-setting-mode-get`] Установить режим реле `/admin/device/relay/setting/mode/{id}`

Параметры: 

- [DeviceRelaySettingModeRequest](../OBJECT.md#DeviceRelaySettingModeRequest) 
