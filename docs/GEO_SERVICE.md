# Документация: Гео-сервис и Зоны доставки (PostGIS)

Этот сервис отвечает за обработку пространственных данных, геокодирование и расчет стоимости доставки.

## 1. Требования
- **База данных**: PostgreSQL 16+ с расширением **PostGIS**.
- **Тип данных**: Колонка `delivery_zones` в таблице `restaurants` должна иметь тип `MULTIPOLYGON` (SRID 4326).
- **API**: Действующий ключ **Google Maps Geocoding API**.

## 2. Настройка окружения (.env)
Добавьте следующие переменные в ваш `.env` файл:

```bash
# Ключ для Google Maps API
GOOGLE_MAPS_API_KEY=your_google_api_key_here

# Стоимость доставки по умолчанию (в манатах)
DELIVERY_FEE_DEFAULT=5
```

## 3. Использование GeoService

Сервис доступен через контейнер зависимостей:
`$geoService = app(App\Domains\Geo\Services\GeoService::class);`

### Основные методы:

#### `geocodeAddress(string $address): ?array`
Преобразует строку адреса в координаты.
- **Результат**: `['lat' => float, 'lon' => float, 'address' => string]` или `null`.
- **Особенности**: Результаты кэшируются на **1 час**.

#### `isPointInDeliveryZone(float $lat, float $lon, string $vendorId): bool`
Проверяет, попадает ли точка в зону доставки конкретного ресторана.

#### `getRestaurantsByPoint(float $lat, float $lon): Collection`
Возвращает список активных ресторанов, чьи зоны доставки покрывают данную точку.
- **Сортировка**: От ближайшего к самому дальнему (`ST_Distance`).
- **Модели**: Каждый ресторан содержит поле `distance`.

#### `calculateDeliveryFee(float $lat, float $lon, string $vendorId): float`
Рассчитывает стоимость доставки.
- Если точка внутри зоны: возвращает `settings->delivery_fee` или 5 TMT.
- Если точка вне зоны: возвращает `settings->delivery_fee_outside` или 5 TMT.

## 4. Пример в коде
```php
public function updateAddress(string $address)
{
    $geo = app(GeoService::class);
    $coords = $geo->geocodeAddress($address);
    
    if ($coords) {
        $restaurants = $geo->getRestaurantsByPoint($coords['lat'], $coords['lon']);
        // ... логика отображения доступных заведений
    }
}
```

GOOGLE_MAPS_API_KEY=AIzaSyA57AG7obcAFjS1JHA2nprV4Q3enfpgXzU