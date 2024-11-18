# Контроллер CameraController `/mobile/cctv`

## Методы

### [POST/index]  `/mobile/cctv/all`

Параметры: 

- [CameraIndexRequest](../OBJECT.md#CameraIndexRequest) request

### [GET/get]  `/mobile/cctv/show/{id}`

Параметры: 

- [CameraGetRequest](../OBJECT.md#CameraGetRequest) request
- `int` id

### [GET/common]  `/mobile/cctv/common`

### [GET/commonDvr]  `/mobile/cctv/common/{id}`

Параметры: 

- [CameraCommonDvrRequest](../OBJECT.md#CameraCommonDvrRequest) request

### [GET/show]  `/mobile/cctv/{cameraId}`

Параметры: 

- [CameraShowRequest](../OBJECT.md#CameraShowRequest) request
- `int` cameraId

### [POST/events]  `/mobile/cctv/events`

Параметры: 

- [CameraEventsRequest](../OBJECT.md#CameraEventsRequest) request
