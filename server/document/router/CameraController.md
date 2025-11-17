# Контроллер CameraController `/mobile/cctv`

## Методы

### [POST/all]  `/mobile/cctv/all`

Параметры: 

- [CameraIndexRequest](../OBJECT.md#CameraIndexRequest) 

### [GET/get]  `/mobile/cctv/show/{id}`

Параметры: 

- [CameraGetRequest](../OBJECT.md#CameraGetRequest) 
- `int` *id*

### [GET/preview]  `/mobile/cctv/preview/{id}`

Параметры: 

- `int` *id*

### [GET/common]  `/mobile/cctv/common`

### [GET/commonDvr]  `/mobile/cctv/common/{id}`

Параметры: 

- [CameraCommonDvrRequest](../OBJECT.md#CameraCommonDvrRequest) 

### [GET/show]  `/mobile/cctv/{id}`

Параметры: 

- [CameraShowRequest](../OBJECT.md#CameraShowRequest) 

### [POST/events]  `/mobile/cctv/events`

Параметры: 

- [CameraEventsRequest](../OBJECT.md#CameraEventsRequest) 

### [POST/motions]  `/mobile/cctv/motions`

Параметры: 

- [CameraEventsRequest](../OBJECT.md#CameraEventsRequest) 

### [POST/move]  `/mobile/cctv/move/{id}`

Параметры: 

- [CameraMoveRequest](../OBJECT.md#CameraMoveRequest) 
