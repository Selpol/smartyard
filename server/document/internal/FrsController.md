# Контроллер FrsController `/internal/frs`

FRS События

## Методы

### [POST/callback] Лицо распознанно `/internal/frs/callback`

Параметры: 

- [FrsCallbackRequest](../OBJECT.md#FrsCallbackRequest) *request*

### [GET/camshot] Скриншот с камеры `/internal/frs/camshot/{id}`

Параметры: 

- `int` *id*

### [GET/face]  `/internal/frs/face/{uuid}`

Параметры: 

- `string` *uuid*
