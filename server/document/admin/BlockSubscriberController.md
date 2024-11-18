# Контроллер BlockSubscriberController `/admin/block/subscriber`

Блокировки абонентов

## Методы

### [GET/index] Получить список блокировок абонента `/admin/block/subscriber/{id}`

Параметры: 

- `int` id

### [POST/store] Добавить блокировку `/admin/block/subscriber`

Параметры: 

- [BlockSubscriberStoreRequest](../OBJECT.md#BlockSubscriberStoreRequest) request

### [PUT/update] Обновить блокировку `/admin/block/subscriber/{id}`

Параметры: 

- [BlockUpdateRequest](../OBJECT.md#BlockUpdateRequest) request

### [DELETE/delete] Удалить блокировку `/admin/block/subscriber/{id}`

Параметры: 

- [BlockDeleteRequest](../OBJECT.md#BlockDeleteRequest) request
