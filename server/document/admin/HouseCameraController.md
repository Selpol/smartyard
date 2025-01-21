# Контроллер HouseCameraController `/admin/house/{id}/camera`

Дом-Камера

## Методы

### [GET/index `house-camera-index-get`] Получить список камер `/admin/house/{id}/camera`

Параметры: 

- `int` *id* Идентификатор квартиры

### [POST/store `house-camera-store-post`] Привязать камеру к дому `/admin/house/{id}/camera/{camera_id}`

Параметры: 

- `int` *id* Идентификатор дома
- `int` *camera_id* Идентификатор камеры

### [DELETE/delete `house-camera-delete-delete`] Отвязать камеру от дома `/admin/house/{id}/camera/{camera_id}`

Параметры: 

- `int` *id* Идентификатор дома
- `int` *camera_id* Идентификатор камеры
