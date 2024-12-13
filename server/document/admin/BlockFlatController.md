# Контроллер BlockFlatController `/admin/block/flat`

Блокировки квартир

## Методы

### [GET/index `block-flat-index-get`] Получить блокировки квартиры `/admin/block/flat/{id}`

Параметры: 

- `int` *id* Идентификатор квартиры

### [POST/store `block-flat-store-post`] Добавить блокировку `/admin/block/flat`

Параметры: 

- [BlockFlatStoreRequest](../OBJECT.md#BlockFlatStoreRequest) 

### [PUT/update `block-flat-update-put`] Обновить блокировку `/admin/block/flat/{id}`

Параметры: 

- [BlockUpdateRequest](../OBJECT.md#BlockUpdateRequest) 

### [DELETE/delete `block-flat-delete-delete`] Удалить блокировку `/admin/block/flat/{id}`

Параметры: 

- [BlockDeleteRequest](../OBJECT.md#BlockDeleteRequest) 
