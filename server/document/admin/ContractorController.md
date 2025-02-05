# Контроллер ContractorController `/admin/contractor`

Подрядчик

## Методы

### [GET/index `contractor-index-get`] Получить список подрядчиков `/admin/contractor`

Параметры: 

- [ContractIndexRequest](../OBJECT.md#ContractIndexRequest) 

### [GET/show `contractor-show-get`] Получить подрядчика `/admin/contractor/{id}`

Параметры: 

- `int` *id*

### [GET/sync `contractor-sync-get`] Синхронизация подрядчика `/admin/contractor/sync`

Параметры: 

- [ContractSyncRequest](../OBJECT.md#ContractSyncRequest) 

### [POST/store `contractor-store-post`] Создать нового подрядчика `/admin/contractor`

Параметры: 

- [ContractStoreRequest](../OBJECT.md#ContractStoreRequest) 

### [PUT/update `contractor-update-put`] Обновить подрядчика `/admin/contractor/{id}`

Параметры: 

- [ContractUpdateRequest](../OBJECT.md#ContractUpdateRequest) 

### [DELETE/delete `contractor-delete-delete`] Удалить подрядчика `/admin/contractor/{id}`

Параметры: 

- `int` *id* Идентификатор подрядчика
