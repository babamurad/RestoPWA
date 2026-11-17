<?php

declare(strict_types=1);

namespace App\Domains\Order\DTO;

final readonly class OrderItemDTO
{
    public function __construct(
        public string $productId,
        public string $productName,
        public int $quantity,
        public int $unitPrice,
        public int $totalPrice,
        public array $modifiers = [],
        public ?string $image = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (string) ($data['product_id'] ?? ''),
            productName: (string) ($data['product_name'] ?? $data['name'] ?? 'Product'),
            quantity: (int) $data['quantity'],
            unitPrice: (int) $data['unit_price'],
            totalPrice: (int) $data['total_price'],
            modifiers: (array) ($data['modifiers'] ?? []),
            image: isset($data['image']) ? (string) $data['image'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
            'modifiers' => $this->modifiers,
            'image' => $this->image,
        ];
    }
}
