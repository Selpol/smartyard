# Контроллер DvrController `/admin/dvr`

Управление DVR серверами

## Методы

### [GET/index `dvr-index-get`] Получить камеры с DVR сервера `/admin/dvr/{id}`

Параметры: 

- `int` *id* Идентификатор DVR сервера

### [GET/show `dvr-show-get`] Получить камеру с сервера `/admin/dvr/show/{id}`

Параметры: 

- [DvrShowRequest](../OBJECT.md#DvrShowRequest) 

### [GET/import `dvr-import-get`] Импортирование камер с сервера `/admin/dvr/import/{id}`

Параметры: 

- [DvrImportRequest](../OBJECT.md#DvrImportRequest) 
