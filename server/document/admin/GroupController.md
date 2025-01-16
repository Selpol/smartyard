# Контроллер GroupController `/admin/group`

Группы

## Методы

### [GET/index `group-index-get`] Получить список групп `/admin/group`

Параметры: 

- [GroupIndexRequest](../OBJECT.md#GroupIndexRequest) 

### [GET/show `group-show-get`] Получить группу `/admin/group/{oid}`

Параметры: 

- `string` *oid* Индентификатор группы

### [POST/store `group-store-post`] Создать новую группу `/admin/group`

Параметры: 

- [GroupStoreRequest](../OBJECT.md#GroupStoreRequest) 

### [PUT/update `group-update-put`] Обновить группу `/admin/group/{oid}`

Параметры: 

- [GroupUpdateRequest](../OBJECT.md#GroupUpdateRequest) 

### [DELETE/delete `group-delete-delete`] Удалить группу `/admin/group/{oid}`

Параметры: 

- `string` *oid*
