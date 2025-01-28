# Контроллер SubscriberFlatController `/admin/subscriber/{id}/flat`

Квартиры абонента

## Методы

### [GET/index `subscriber-flat-index-get`] Получить квартиры абонента `/admin/subscriber/{id}/flat`

Параметры: 

- `int` *id*

### [POST/store `subscriber-flat-store-post`] Привязать квартиру к абоненту `/admin/subscriber/{id}/flat/{flat_id}`

Параметры: 

- [SubscriberFlatRequest](../OBJECT.md#SubscriberFlatRequest) 

### [DELETE/delete `subscriber-flat-delete-delete`] Отвязать квартиру от абонента `/admin/subscriber/{id}/flat/{flat_id}`

Параметры: 

- [SubscriberFlatRequest](../OBJECT.md#SubscriberFlatRequest) 
