<?php

declare(strict_types=1);

namespace App\Livewire\Geo;

use App\Domains\Geo\Services\GeoService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AddressSelector extends Component
{
    public bool $isAddressModalOpen = false;

    public string $address = '';

    public float $lat = 0;

    public float $lon = 0;

    public array $suggestions = [];

    public string $selectedVendorId = '';

    public bool $isInDeliveryZone = false;

    public bool $isDetectingLocation = false;

    public ?string $error = null;

    public string $source = '';

    public ?string $provider = null;

    public string $manualAddress = '';

    public string $landmark = '';

    public string $entrance = '';

    public string $floor = '';

    public string $apartment = '';

    public string $courierComment = '';

    public bool $showRefinement = false;

    public bool $hasSelectedPoint = false;

    private GeoService $geoService;

    public function mount(): void
    {
        $this->isAddressModalOpen = false;
    }

    #[On('open-address-selector')]
    public function openModal(): void
    {
        $this->selectedVendorId = session('current_vendor_id', '');
        $this->showRefinement = false;
        $this->isAddressModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isAddressModalOpen = false;
    }

    public function boot(): void
    {
        $this->geoService = app(GeoService::class);
        $this->selectedVendorId = session('current_vendor_id', '');
        $savedAddress = session('current_address');
        if ($savedAddress) {
            $this->address = $savedAddress['address'] ?? '';
            $this->lat = $savedAddress['lat'] ?? 0;
            $this->lon = $savedAddress['lon'] ?? 0;
            $this->source = $savedAddress['source'] ?? '';
            $this->provider = $savedAddress['provider'] ?? null;
            $this->manualAddress = $savedAddress['manual_address'] ?? '';
            $this->landmark = $savedAddress['landmark'] ?? '';
            $this->entrance = $savedAddress['entrance'] ?? '';
            $this->floor = $savedAddress['floor'] ?? '';
            $this->apartment = $savedAddress['apartment'] ?? '';
            $this->courierComment = $savedAddress['courier_comment'] ?? '';
            if ($this->lat && $this->lon) {
                $this->hasSelectedPoint = true;
                if ($this->selectedVendorId) {
                    $this->isInDeliveryZone = $this->geoService->isPointInDeliveryZone(
                        $this->lat,
                        $this->lon,
                        $this->selectedVendorId
                    );
                }
            }
        } else {
            // Default center for map, but NOT a selected point
            $this->lat = 39.0886;
            $this->lon = 63.5593;
            $this->hasSelectedPoint = false;
        }
    }

    public function detectLocation(): void
    {
        $this->error = null;
        $this->isDetectingLocation = true;

        $this->js(<<<'JS'
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        $wire.setLocation(
                            position.coords.latitude,
                            position.coords.longitude,
                            'gps'
                        );
                        window.dispatchEvent(new CustomEvent('map-update', { 
                            detail: { lat: position.coords.latitude, lon: position.coords.longitude } 
                        }));
                    },
                    (error) => {
                        let msg = 'Не удалось определить местоположение';
                        if (error.code === 1) {
                            msg = 'Доступ к геолокации запрещён. Поставьте точку на карте вручную.';
                        } else if (error.code === 2) {
                            msg = 'Не удалось получить координаты. Попробуйте ещё раз или поставьте точку на карте.';
                        } else if (error.code === 3) {
                            msg = 'Время ожидания геолокации истекло. Поставьте точку на карте вручную.';
                        }
                        $wire.gpsError(msg);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            } else {
                $wire.gpsError('Геолокация не поддерживается вашим браузером. Поставьте точку на карте вручную.');
            }
        JS);
    }

    public function gpsError(string $message): void
    {
        $this->error = $message;
        $this->isDetectingLocation = false;
        Log::info('[AddressSelector] GPS error', ['message' => $message]);
    }

    public function setLocation(float $lat, float $lon, string $source = 'map_pin'): void
    {
        Log::info('[AddressSelector] setLocation called', ['lat' => $lat, 'lon' => $lon, 'source' => $source]);
        $this->lat = $lat;
        $this->lon = $lon;
        $this->source = $source;
        $this->isDetectingLocation = false;
        $this->error = null;
        $this->hasSelectedPoint = true;

        $result = $this->geoService->reverseGeocode($lat, $lon);

        if ($result) {
            $this->address = $result['address'];
            $this->provider = $result['provider'] ?? null;
        } else {
            // Don't clear address if we have it manually, but if it was from geocoding, maybe clear
            if ($this->source === 'map_pin' || $this->source === 'gps') {
                $this->address = '';
            }
            $this->provider = null;
        }

        $this->checkDeliveryZone();
        
        // No map-update dispatch here because setLocation is often called FROM the map itself.
        // If we need to sync from server to map, we do it in methods that are NOT triggered by map.
    }

    public function setError(string $error): void
    {
        $this->error = $error;
        $this->isDetectingLocation = false;
    }

    public function setIsDetectingLocation(bool $value): void
    {
        $this->isDetectingLocation = $value;
    }

    public function searchAddress(string $query): void
    {
        if (strlen($query) < 3) {
            $this->suggestions = [];

            return;
        }

        $this->error = null;
        $this->suggestions = $this->geoService->suggestAddresses($query);
    }

    public function selectAddress(int $index): void
    {
        if (! isset($this->suggestions[$index])) {
            return;
        }

        $selected = $this->suggestions[$index];
        Log::info('[AddressSelector] selectAddress called', ['index' => $index, 'address' => $selected['address']]);
        $this->address = $selected['address'];
        $this->lat = $selected['lat'];
        $this->lon = $selected['lon'];
        $this->source = $selected['source'] ?? 'suggestion';
        $this->provider = $selected['source'] ?? null;
        $this->suggestions = [];
        $this->error = null;
        $this->hasSelectedPoint = true;
        
        $this->dispatch('map-update', lat: $this->lat, lon: $this->lon);

        if (! $this->selectedVendorId) {
            $this->error = 'Выберите ресторан для проверки зоны доставки';

            return;
        }

        $this->checkDeliveryZone();
    }

    public function goToRefinement(): void
    {
        $hasCoords = $this->hasSelectedPoint && $this->lat && $this->lon;
        
        if (! $hasCoords) {
            if (empty($this->address)) {
                $this->error = 'Выберите точку на карте или введите адрес';
                return;
            }

            $this->isDetectingLocation = true;
            // Use fallback geocoding for better reliability
            $result = $this->geoService->geocodeWithFallback($this->address);

            if ($result) {
                $this->address = $result['address'];
                $this->lat = $result['lat'];
                $this->lon = $result['lon'];
                $this->source = 'manual_geocoded';
                $this->provider = $result['provider'] ?? null;
                $this->hasSelectedPoint = true;
                $this->dispatch('map-update', lat: $this->lat, lon: $this->lon);
            } else {
                $this->error = 'Адрес не найден. Пожалуйста, укажите точку на карте вручную.';
                $this->isDetectingLocation = false;
                return;
            }
            $this->isDetectingLocation = false;
        }

        if (! $this->selectedVendorId) {
            $this->error = 'Выберите ресторан для проверки зоны доставки';
            return;
        }

        $this->checkDeliveryZone();

        if (! $this->isInDeliveryZone) {
            return;
        }

        $this->showRefinement = true;
    }

    public function confirmAddress(): void
    {
        if (! $this->hasSelectedPoint || ! $this->lat || ! $this->lon) {
            $this->error = 'Выберите точку на карте';
            return;
        }

        if (! $this->selectedVendorId) {
            $this->error = 'Выберите ресторан для проверки зоны доставки';
            return;
        }

        if (! $this->isInDeliveryZone) {
            $this->checkDeliveryZone();
            if (! $this->isInDeliveryZone) {
                return;
            }
        }

        $addressData = [
            'address' => $this->address,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'source' => $this->source,
            'provider' => $this->provider,
            'manual_address' => $this->manualAddress,
            'landmark' => $this->landmark,
            'entrance' => $this->entrance,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'courier_comment' => $this->courierComment,
        ];

        session(['current_address' => $addressData]);

        $this->dispatch('address-selected', ...$addressData);

        $this->isAddressModalOpen = false;
    }

    public function backToMap(): void
    {
        $this->showRefinement = false;
        $this->error = null;
    }

    private function checkDeliveryZone(): void
    {
        if (! $this->selectedVendorId) {
            $this->error = 'Выберите ресторан для проверки зоны доставки';
            return;
        }

        if (! $this->lat || ! $this->lon) {
            return;
        }

        $result = $this->geoService->checkDeliveryZone(
            $this->lat,
            $this->lon,
            $this->selectedVendorId
        );

        $this->isInDeliveryZone = $result->isAllowed();

        if ($this->isInDeliveryZone) {
            $this->error = null;
        } else {
            $this->error = $result->messageForUser();
        }
    }

    public function updatedAddress(string $value): void
    {
        $this->searchAddress($value);
    }

    public function render(): View
    {
        return view('livewire.geo.address-selector');
    }
}
