# Контроллер UserPermissionController `/admin/user/permission/{id}`

Пользователь права

## Методы

### [GET/index `user-permission-index-get`] Получить список прав пользователя `/admin/user/permission/{id}`

Параметры: 

- `int` *id* Идентификатор пользователя

### [POST/store `user-permission-store-post`] Привязать право к пользователю `/admin/user/permission/{id}/{permission_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *permission_id* Идентификатор права

### [DELETE/delete `user-permission-delete-delete`] Отвязать право от пользователя `/admin/user/permission/{id}/{permission_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *permission_id* Идентификатор права
