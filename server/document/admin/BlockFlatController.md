# Контроллер BlockFlatController `/admin/block/flat`

Блокировки квартир

## Методы

### [GET/index] Получить блокировки квартиры `/admin/block/flat/{id}`

Параметры: 

- `int` id

### [POST/store] Добавить блокировку `/admin/block/flat`

Параметры: 

- [BlockFlatStoreRequest](../OBJECT.md#BlockFlatStoreRequest) request

### [PUT/update] Обновить блокировку `/admin/block/flat/{id}`

Параметры: 

- [BlockUpdateRequest](../OBJECT.md#BlockUpdateRequest) request

### [DELETE/delete] Удалить блокировку `/admin/block/flat/{id}`

Параметры: 

- [BlockDeleteRequest](../OBJECT.md#BlockDeleteRequest) request
