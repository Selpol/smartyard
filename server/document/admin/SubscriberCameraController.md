# Контроллер SubscriberCameraController `/admin/subscriber/camera`

Камеры абонента

## Методы

### [GET/index `subscriber-camera-index-get`] Получить камеры абонента `/admin/subscriber/camera/{subscriber_id}`

Параметры: 

- `int` *subscriber_id*

### [POST/store `subscriber-camera-store-post`] Привязать камеру к абоненту `/admin/subscriber/camera/{subscriber_id}`

Параметры: 

- [SubscriberCameraRequest](../OBJECT.md#SubscriberCameraRequest) 

### [POST/delete `subscriber-camera-delete-post`] Отвязать камеру от абонента `/admin/subscriber/camera/{subscriber_id}`

Параметры: 

- [SubscriberCameraRequest](../OBJECT.md#SubscriberCameraRequest) 
