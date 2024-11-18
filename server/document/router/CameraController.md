# Контроллер CameraController `/mobile/cctv`

## Методы

### [POST/index]  `/mobile/cctv/all`

Параметры: 

- [CameraIndexRequest](../OBJECT.md#CameraIndexRequest) 

### [GET/get]  `/mobile/cctv/show/{id}`

Параметры: 

- [CameraGetRequest](../OBJECT.md#CameraGetRequest) 
- `int` *id*

### [GET/common]  `/mobile/cctv/common`

### [GET/commonDvr]  `/mobile/cctv/common/{id}`

Параметры: 

- [CameraCommonDvrRequest](../OBJECT.md#CameraCommonDvrRequest) 

### [GET/show]  `/mobile/cctv/{cameraId}`

Параметры: 

- [CameraShowRequest](../OBJECT.md#CameraShowRequest) 
- `int` *cameraId*

### [POST/events]  `/mobile/cctv/events`

Параметры: 

- [CameraEventsRequest](../OBJECT.md#CameraEventsRequest) 
