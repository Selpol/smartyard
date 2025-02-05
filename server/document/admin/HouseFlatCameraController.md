# Контроллер HouseFlatCameraController `/admin/house/flat/{id}/camera`

Квартира-Камера

## Методы

### [GET/index `house-flat-camera-index-get`] Получить список камер `/admin/house/flat/{id}/camera`

Параметры: 

- `int` *id* Идентификатор квартиры

### [POST/store `house-flat-camera-store-post`] Привязать камеру к квартире `/admin/house/flat/{id}/camera/{camera_id}`

Параметры: 

- `int` *id* Идентификатор квартиры
- `int` *camera_id* Идентификатор камеры

### [DELETE/delete `house-flat-camera-delete-delete`] Отвязать камеру от квартиры `/admin/house/flat/{id}/camera/{camera_id}`

Параметры: 

- `int` *id* Идентификатор квартиры
- `int` *camera_id* Идентификатор камеры
