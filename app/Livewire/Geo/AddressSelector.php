<?php

declare(strict_types=1);

namespace App\Livewire\Geo;

use App\Domains\Geo\Services\GeoService;
use Illuminate\Support\Facades\Http;
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

    private GeoService $geoService;
    
    public function mount(): void
    {
        $this->isAddressModalOpen = false;
    }

    #[On('open-address-selector')]
    public function openModal(): void
    {
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
            if ($this->lat && $this->lon && $this->selectedVendorId) {
                $this->isInDeliveryZone = $this->geoService->isPointInDeliveryZone(
                    $this->lat,
                    $this->lon,
                    $this->selectedVendorId
                );
            }
        } else {
            // По умолчанию - Туркменабат
            $this->lat = 39.0886;
            $this->lon = 63.5593;
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
                        $wire.setLocation(position.coords.latitude, position.coords.longitude);
                    },
                    (error) => {
                        $wire.setError('Не удалось определить местоположение: ' + error.message);
                        $wire.setIsDetectingLocation(false);
                    }
                );
            } else {
                $wire.setError('Геолокация не поддерживается браузером');
                $wire.setIsDetectingLocation(false);
            }
        JS);
    }

    public function setLocation(float $lat, float $lon): void
    {
        $this->lat = $lat;
        $this->lon = $lon;
        $this->isDetectingLocation = false;

        $result = $this->geoService->reverseGeocode($lat, $lon);

        if ($result) {
            $this->address = $result['address'];
            $this->checkDeliveryZone();
        } else {
            $this->error = 'Не удалось определить адрес по координатам';
        }
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
        $this->address = $selected['address'];
        $this->lat = $selected['lat'];
        $this->lon = $selected['lon'];
        $this->suggestions = [];
        $this->error = null;

        if (! $this->selectedVendorId) {
            $this->error = 'Выберите ресторан для проверки зоны доставки';

            return;
        }

        $this->checkDeliveryZone();
    }

    public function confirmAddress(): void
    {
        if (empty($this->address)) {
            $this->error = 'Введите адрес';
            return;
        }

        $this->error = null;

        // Если координаты уже известны (выбрали из подсказок или через геолокацию) —
        // повторный geocoding не нужен, сразу проверяем зону доставки
        if ($this->lat && $this->lon) {
            $this->checkDeliveryZone();
            return;
        }

        $this->isDetectingLocation = true;

        $result = $this->geoService->geocodeAddress($this->address);

        if ($result) {
            $this->address = $result['address'];
            $this->lat = $result['lat'];
            $this->lon = $result['lon'];
            $this->checkDeliveryZone();
        } else {
            $this->error = 'Не удалось найти этот адрес. Пожалуйста, уточните его.';
        }

        $this->isDetectingLocation = false;
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

        $this->isInDeliveryZone = $this->geoService->isPointInDeliveryZone(
            $this->lat,
            $this->lon,
            $this->selectedVendorId
        );

        if (! $this->isInDeliveryZone) {
            $this->error = 'Этот адрес находится за пределами зоны доставки выбранного ресторана';

            return;
        }

        session([
            'current_address' => [
                'address' => $this->address,
                'lat' => $this->lat,
                'lon' => $this->lon,
            ],
        ]);

        $this->dispatch('address-selected', 
            address: $this->address,
            lat: $this->lat,
            lon: $this->lon
        );

        $this->isAddressModalOpen = false;
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
