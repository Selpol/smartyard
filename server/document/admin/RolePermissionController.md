# Контроллер RolePermissionController `/admin/role/{id}/permission`

Роль права

## Методы

### [GET/index `role-permission-index-get`] Получить список прав роли `/admin/role/{id}/permission`

Параметры: 

- `int` *id* Идентификатор роли

### [POST/store `role-permission-store-post`] Привязать право к роли `/admin/role/{id}/permission/{permission_id}`

Параметры: 

- `int` *id* Идентификатор роли
- `int` *permission_id* Идентификатор права

### [DELETE/delete `role-permission-delete-delete`] Отвязать право от роли `/admin/role/{id}/permission/{permission_id}`

Параметры: 

- `int` *id* Идентификатор роли
- `int` *permission_id* Идентификатор права
