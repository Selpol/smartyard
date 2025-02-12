# Контроллер CameraController `/admin/camera`

Камера

## Методы

### [GET/index `camera-index-get`] Получить список камер `/admin/camera`

Параметры: 

- [CameraIndexRequest](../OBJECT.md#CameraIndexRequest) 

### [GET/show `camera-show-get`] Получить камеру `/admin/camera/{id}`

Параметры: 

- `int` *id*

### [GET/screenshot `camera-screenshot-get`] Получить скриншот с камеры `/admin/camera/screenshot/{id}`

Параметры: 

- `int` *id*

### [POST/store `camera-store-post`] Создать новую камеру `/admin/camera`

Параметры: 

- [CameraStoreRequest](../OBJECT.md#CameraStoreRequest) 

### [PUT/update `camera-update-put`] Обновить камеру `/admin/camera/{id}`

Параметры: 

- [CameraUpdateRequest](../OBJECT.md#CameraUpdateRequest) 

### [DELETE/delete `camera-delete-delete`] Удалить камеру `/admin/camera/{id}`

Параметры: 

- `int` *id*
