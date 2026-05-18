Рекомендуемый план дальнейших действий
Этап 1. Стабилизировать модель зоны доставки
Цель: сделать один официальный формат хранения и чтения delivery_zones.

Починить вызовы $record->deliveryZones() в Filament-ресурсах.

Заменить на $record->getZonesArray() или вернуть публичный метод.

Затронутые места: admin resource и vendor resource. 

Перенести PostGIS-конвертацию из accessor в отдельный сервис или метод репозитория.

Accessor удобен, но для DB::raw(...) и SQL geometry это слишком непрозрачное место. 

Сохранять только валидный MULTIPOLYGON SRID 4326.

Миграция уже ожидает именно MULTIPOLYGON. 

Добавить нормализацию входящих форматов:

Polygon → MultiPolygon;

MultiPolygon → оставить;

Feature → достать geometry;

FeatureCollection → собрать polygon/multipolygon в MultiPolygon.

Этап 2. Унифицировать все UI для редактирования зоны
Сейчас есть как минимум два разных пути:

Filament Yandex map field. 

Старый vendor settings на Leaflet. 

Рекомендация: выбрать один основной путь.

Лучший вариант:

Оставить Filament/Yandex как основной редактор зоны.

Старый Leaflet settings либо удалить, либо переписать на тот же компонент/формат.

Везде сохранять один и тот же MultiPolygon.

Везде читать через один публичный метод получения GeoJSON.

Этап 3. Сделать проверку зоны диагностичной
Сейчас bool уже недостаточен. Нужно заменить внутреннюю проверку на более информативную.

Рекомендованный интерфейс:

DeliveryZoneCheckResult {
    status: inside|outside|zone_missing|invalid_geometry|postgis_error
    isAllowed(): bool
    messageForUser(): string
    debugContext(): array
}
Где использовать:

AddressSelector::checkDeliveryZone(). 

CheckoutWizard::validateAddress(). 

OrderPreconditionValidator::validateAddress(). 

Это позволит отличать:

“ресторан не настроил зону”;

“геометрия повреждена”;

“точка реально вне зоны”;

“PostGIS недоступен”.

Этап 4. Закрыть очевидную UX-регрессию выбора подсказки
selectAddress() должен выставлять hasSelectedPoint = true, потому что подсказка уже содержит координаты. Сейчас этого нет. 

Также стоит проверить, что после выбора подсказки карта обновляется через map-update, и что confirmAddress() не пытается повторно геокодить уже выбранный адрес. 

Этап 5. Привести конфиг геопровайдеров к реальному поведению
Либо использовать GEO_PROVIDERS из .env.example. 

Либо убрать его и оставить GEO_FALLBACK_FIRST.

Дефолт GEO_DRIVER=yandex в .env.example уже выглядит логично для текущей карты и региона. 

В config/services.php сейчас всё ещё дефолт geo_driver — google, если env не задан. Лучше привести дефолт к yandex, чтобы fresh install не уходил в Google без ключа. 

Этап 6. Покрыть тестами минимум критичных сценариев
Нужны не только unit-тесты, а слой “данные → сохранение зоны → проверка точки”.

Минимальный набор:

GeoJSON normalizer tests

Polygon превращается в MultiPolygon.

Feature превращается в geometry.

FeatureCollection превращается в MultiPolygon.

некорректный JSON даёт понятную ошибку.

Restaurant zone persistence tests

зона сохраняется как валидная geometry;

чтение возвращает GeoJSON;

пустая зона не ломает админку.

GeoService tests

точка внутри зоны → allowed;

точка на границе → allowed;

точка вне зоны → denied;

delivery_zones = NULL → zone_not_configured, а не просто outside.

Livewire AddressSelector tests

дефолтный центр карты не считается выбранной точкой;

GPS/клик/drag выставляют hasSelectedPoint;

выбор подсказки выставляет hasSelectedPoint;

ручной адрес вызывает fallback.

Checkout/Order tests

заказ не создаётся без координат;

заказ не создаётся вне зоны;

заказ не создаётся при ненастроенной зоне с корректной причиной;

заказ создаётся внутри зоны.

Этап 7. Провести ручную E2E-проверку после фиксов
Сценарии ручной проверки:

Новый ресторан без зоны:

открыть checkout;

выбрать точку;

получить понятное сообщение “зона доставки ресторана не настроена”.

Ресторан с зоной:

точка внутри;

точка на границе;

точка вне зоны.

Карта недоступна:

отключить/убрать YANDEX_MAPS_JS_KEY;

убедиться, что нет вечной загрузки;

ручной ввод работает через fallback.

Подсказки:

локальное место;

Яндекс;

Nominatim fallback;

пустой результат.

Сохранение зоны:

нарисовать зону в админке;

сохранить;

открыть повторно;

убедиться, что зона отображается;

проверить точку внутри этой зоны.