# Контроллер AddressRegionController `/admin/address/region`

Адрес-Регион

## Методы

### [GET/index `address-region-index-get`] Получить список регионов `/admin/address/region`

Параметры: 

- [PageRequest](../OBJECT.md#PageRequest) 

### [GET/show `address-region-show-get`] Получить регион `/admin/address/region/{id}`

Параметры: 

- `int` *id* Идентификатор региона

### [POST/store `address-region-store-post`] Создать новый регион `/admin/address/region`

Параметры: 

- [AddressRegionStoreRequest](../OBJECT.md#AddressRegionStoreRequest) 

### [PUT/update `address-region-update-put`] Обновить регион `/admin/address/region/{id}`

Параметры: 

- [AddressRegionUpdateRequest](../OBJECT.md#AddressRegionUpdateRequest) 

### [DELETE/delete `address-region-delete-delete`] Удалить регион `/admin/address/region/{id}`

Параметры: 

- `int` *id* Идентификатор региона
