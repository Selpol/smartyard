# Контроллер ScheduleController `/admin/schedule`

Расписание

## Методы

### [GET/index `schedule-index-get`] Получить список расписания `/admin/schedule`

Параметры: 

- [ScheduleIndexRequest](../OBJECT.md#ScheduleIndexRequest) 

### [GET/show `schedule-show-get`] Получить расписание `/admin/schedule/{id}`

Параметры: 

- `int` *id* Идентификатор расписания

### [POST/store `schedule-store-post`] Создать новое расписание `/admin/schedule`

Параметры: 

- [ScheduleStoreRequest](../OBJECT.md#ScheduleStoreRequest) 

### [PUT/update `schedule-update-put`] Обновить расписание `/admin/schedule/{id}`

Параметры: 

- [ScheduleUpdateRequest](../OBJECT.md#ScheduleUpdateRequest) 

### [DELETE/delete `schedule-delete-delete`] Удалить расписание `/admin/schedule/{id}`

Параметры: 

- `int` *id* Идентификатор расписания
