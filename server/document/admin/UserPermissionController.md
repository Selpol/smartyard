# Контроллер UserPermissionController `/admin/user/{id}/permission`

Пользователь права

## Методы

### [GET/index `user-permission-index-get`] Получить список прав пользователя `/admin/user/{id}/permission`

Параметры: 

- `int` *id* Идентификатор пользователя

### [POST/store `user-permission-store-post`] Привязать право к пользователю `/admin/user/{id}/permission/{permission_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *permission_id* Идентификатор права

### [DELETE/delete `user-permission-delete-delete`] Отвязать право от пользователя `/admin/user/{id}/permission/{permission_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *permission_id* Идентификатор права
