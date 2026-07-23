<?php

declare(strict_types=1);

namespace App\Domains\Order\DTO;

final readonly class OrderSubmitDTO
{
    public function __construct(
        public string $vendorId,
        public array $items,
        public int $total,
        public array $address,
        public int $deliveryFee,
        public ?string $deliveryTime,
        public string $paymentMethod,
        public ?string $comment,
        public ?string $idempotencyKey,
        public bool $isOffline,
        public string $traceId,
        public string $customerName,
        public string $customerPhone,
    ) {
    }

    public static function fromArray(array $data, string $traceId): self
    {
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = OrderItemDTO::fromArray($item);
        }

        return new self(
            vendorId: (string) ($data['vendor_id'] ?? ''),
            items: $items,
            total: (int) $data['total'],
            address: (array) ($data['address'] ?? []),
            deliveryFee: (int) ($data['delivery_fee'] ?? 0),
            deliveryTime: isset($data['delivery_time']) ? (string) $data['delivery_time'] : null,
            paymentMethod: (string) ($data['payment_method'] ?? 'cash'),
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null,
            isOffline: (bool) ($data['is_offline'] ?? false),
            traceId: $traceId,
            customerName: (string) ($data['customer_name'] ?? ''),
            customerPhone: (string) ($data['customer_phone'] ?? ''),
        );
    }

    public function toArray(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }

        return [
            'vendor_id' => $this->vendorId,
            'items' => $items,
            'total' => $this->total,
            'address' => $this->address,
            'delivery_fee' => $this->deliveryFee,
            'delivery_time' => $this->deliveryTime,
            'payment_method' => $this->paymentMethod,
            'comment' => $this->comment,
            'idempotency_key' => $this->idempotencyKey,
            'is_offline' => $this->isOffline,
            'trace_id' => $this->traceId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
        ];
    }

    public function toOrderServiceData(?string $userId): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }

        $address = $this->address;
        $address['name'] = $this->customerName;
        $address['phone'] = $this->customerPhone;

        return [
            'vendor_id' => $this->vendorId,
            'user_id' => $userId,
            'address' => $address,
            'items' => $items,
            'total' => $this->total,
            'delivery_fee' => $this->deliveryFee,
            'delivery_time' => $this->deliveryTime,
            'payment_method' => $this->paymentMethod,
            'comment' => $this->comment,
            'created_via' => 'pwa',
            'is_offline' => $this->isOffline,
            'idempotency_key' => $this->idempotencyKey,
            'trace_id' => $this->traceId,
            'metadata' => [
                'address_source' => $this->address['address_source'] ?? 'unknown',
                'geolocate_status' => $this->address['geolocate_status'] ?? null,
                'geolocate_accuracy_m' => isset($this->address['geolocate_accuracy_m']) ? (float) $this->address['geolocate_accuracy_m'] : null,
            ],
        ];
    }
}
