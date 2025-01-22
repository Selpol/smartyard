# Контроллер UserRoleController `/admin/user/{id}/role`

Пользователь роль

## Методы

### [GET/index `user-role-index-get`] Получить список ролей пользователя `/admin/user/{id}/role`

Параметры: 

- `int` *id* Идентификатор пользователя

### [POST/store `user-role-store-post`] Привязать роль к пользователю `/admin/user/{id}/role/{role_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *role_id* Идентификатор роли

### [DELETE/delete `user-role-delete-delete`] Отвязать роль от пользователя `/admin/user/{id}/role/{role_id}`

Параметры: 

- `int` *id* Идентификатор пользователя
- `int` *role_id* Идентификатор роли
