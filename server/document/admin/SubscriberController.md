# Контроллер SubscriberController `/admin/subscriber`

Абоненты

## Методы

### [GET/index `subscriber-index-get`] Получить список абонентов `/admin/subscriber`

Параметры: 

- [SubscriberRequest](../OBJECT.md#SubscriberRequest) 

### [GET/show `subscriber-show-get`] Получить абонента `/admin/subscriber/{house_subscriber_id}`

Параметры: 

- `int` *house_subscriber_id* Идентификатор абонента

### [POST/store `subscriber-store-post`] Создать нового абонента `/admin/subscriber`

Параметры: 

- [SubscriberStoreRequest](../OBJECT.md#SubscriberStoreRequest) 

### [PUT/update `subscriber-update-put`] Обновить абонента `/admin/subscriber/{house_subscriber_id}`

Параметры: 

- [SubscriberUpdateRequest](../OBJECT.md#SubscriberUpdateRequest) 

### [DELETE/delete `subscriber-delete-delete`] Удалить абонента `/admin/subscriber/{house_subscriber_id}`

Параметры: 

- `int` *house_subscriber_id* Идентификатор абонента
