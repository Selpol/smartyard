# Контроллер AddressHouseController `/admin/address/house`

Адрес-Дом

## Методы

### [GET/index `address-house-index-get`] Получить список домов `/admin/address/house`

Параметры: 

- [AddressHouseIndexRequest](../OBJECT.md#AddressHouseIndexRequest) 

### [GET/show `address-house-show-get`] Получить дом `/admin/address/house/{id}`

Параметры: 

- `int` *id* Идентификатор дома

### [GET/qr `address-house-qr-get`] Получить QR с адреса `/admin/address/house/qr/{id}`

Параметры: 

- [AddressHouseQrRequest](../OBJECT.md#AddressHouseQrRequest) 

### [POST/store `address-house-store-post`] Создать новый дом `/admin/address/house`

Параметры: 

- [AddressHouseStoreRequest](../OBJECT.md#AddressHouseStoreRequest) 

### [POST/magic `address-house-magic-post`] Автоматически создать дом `/admin/address/house/magic`

Параметры: 

- [AddressHouseMagicRequest](../OBJECT.md#AddressHouseMagicRequest) 

### [PUT/update `address-house-update-put`] Обновить дом `/admin/address/house/{id}`

Параметры: 

- [AddressHouseUpdateRequest](../OBJECT.md#AddressHouseUpdateRequest) 

### [DELETE/delete `address-house-delete-delete`] Удалить дом `/admin/address/house/{id}`

Параметры: 

- `int` *id* Идентификатор дома
