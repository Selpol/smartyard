# Контроллер IntercomController `/admin/intercom`

Домофон

## Методы

### [GET/index `intercom-index-get`] Получить список домофонов `/admin/intercom`

Параметры: 

- [IntercomIndexRequest](../OBJECT.md#IntercomIndexRequest) 

### [GET/show `intercom-show-get`] Получить домофон `/admin/intercom/{id}`

Параметры: 

- `int` *id* Идентификатор домофона

### [POST/store `intercom-store-post`] Создать новый домофон `/admin/intercom`

Параметры: 

- [IntercomStoreRequest](../OBJECT.md#IntercomStoreRequest) 

### [PUT/update `intercom-update-put`] Обновить домофон `/admin/intercom/{id}`

Параметры: 

- [IntercomUpdateRequest](../OBJECT.md#IntercomUpdateRequest) 

### [DELETE/delete `intercom-delete-delete`] Удалить домофон `/admin/intercom/{id}`

Параметры: 

- `int` *id* Идентификатор домофона
