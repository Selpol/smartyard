# Контроллер SubscriberFlatController `/admin/subscriber/flat/{house_subscriber_id}`

Квартиры абонента

## Методы

### [GET/index `subscriber-flat-index-get`] Получить квартиры абонента `/admin/subscriber/flat/{house_subscriber_id}`

Параметры: 

- `int` *house_subscriber_id* Идентификатор абонента

### [POST/store `subscriber-flat-store-post`] Привязать квартиру к абоненту `/admin/subscriber/flat/{house_subscriber_id}/{flat_id}`

Параметры: 

- [SubscriberFlatRequest](../OBJECT.md#SubscriberFlatRequest) 

### [DELETE/delete `subscriber-flat-delete-delete`] Отвязать квартиру от абонента `/admin/subscriber/flat/{house_subscriber_id}/{flat_id}`

Параметры: 

- [SubscriberFlatRequest](../OBJECT.md#SubscriberFlatRequest) 
