# Контроллер KeyController `/admin/key`

Ключ

## Методы

### [GET/index `key-index-get`] Получить список ключей `/admin/key`

### [GET/show `key-show-get`] Получить ключ `/admin/key/{id}`

Параметры: 

- `int` *id* Идентификатор ключа

### [POST/store `key-store-post`] Добавить ключ `/admin/key`

Параметры: 

- [KeyStoreRequest](../OBJECT.md#KeyStoreRequest) 

### [PUT/update `key-update-put`] Обновить ключ `/admin/key/{id}`

Параметры: 

- [KeyUpdateRequest](../OBJECT.md#KeyUpdateRequest) 

### [DELETE/delete `key-delete-delete`] Удалить ключ `/admin/key/{id}`

Параметры: 

- `int` *id* Идентификатор ключа
