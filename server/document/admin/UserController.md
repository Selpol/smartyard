# Контроллер UserController `/admin/user`

Пользователь

## Методы

### [GET/index `user-index-get`] Получить список пользователей `/admin/user`

### [GET/show `user-show-get`] Получить пользователя `/admin/user/{id}`

Параметры: 

- `int` *id* Идентификатор пользователя

### [POST/store `user-store-post`] Создать нового пользователя `/admin/user`

Параметры: 

- [UserStoreRequest](../OBJECT.md#UserStoreRequest) 

### [PUT/update `user-update-put`] Обновить пользователя `/admin/user/{id}`

Параметры: 

- [UserUpdateRequest](../OBJECT.md#UserUpdateRequest) 

### [DELETE/delete `user-delete-delete`] Удалить пользователя `/admin/user/{id}`

Параметры: 

- `int` *id* Идентификатор пользователя
