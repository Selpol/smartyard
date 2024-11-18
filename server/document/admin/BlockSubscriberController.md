# Контроллер BlockSubscriberController `/admin/block/subscriber`

Блокировки абонентов

## Методы

### [GET/index `block-subscriber-index-get`] Получить список блокировок абонента `/admin/block/subscriber/{id}`

Параметры: 

- `int` *id*

### [POST/store `block-subscriber-store-post`] Добавить блокировку `/admin/block/subscriber`

Параметры: 

- [BlockSubscriberStoreRequest](../OBJECT.md#BlockSubscriberStoreRequest) *request*

### [PUT/update `block-subscriber-update-put`] Обновить блокировку `/admin/block/subscriber/{id}`

Параметры: 

- [BlockUpdateRequest](../OBJECT.md#BlockUpdateRequest) *request*

### [DELETE/delete `block-subscriber-delete-delete`] Удалить блокировку `/admin/block/subscriber/{id}`

Параметры: 

- [BlockDeleteRequest](../OBJECT.md#BlockDeleteRequest) *request*
