<?php

declare(strict_types=1);

namespace App\Livewire\Menu;

use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use Livewire\Component;

class Catalog extends Component
{
    public string $vendorId = '';

    public ?int $categoryId = null;

    public array $products = [];

    public int $page = 1;

    public bool $hasMorePages = true;

    public array $categories = [];

    public string $selectedProductId = '';

    public array $selectedModifiers = [];

    public int $selectedQuantity = 1;

    public int $selectedProductPrice = 0;

    public string $selectedProductName = '';

    private const PER_PAGE = 20;

    public function mount(): void
    {
        $this->loadCategories();
        $this->loadProducts();
    }

    public function loadCategories(): void
    {
        $this->categories = Category::where('vendor_id', $this->vendorId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function loadProducts(): void
    {
        $query = Product::where('vendor_id', $this->vendorId)
            ->available()
            ->with('category');

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        $products = $query->paginate(self::PER_PAGE, ['*'], 'page', $this->page);

        $this->hasMorePages = $products->hasMorePages();

        $newProducts = array_map(function ($product) {
            $minPrice = $this->calculateMinPrice($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (int) $product->price,
                'min_price' => $minPrice,
                'description' => $product->description,
                'image' => $product->image ? asset('storage/'.$product->image) : null,
                'modifiers' => $product->modifiers ? $product->modifiers->toArray() : [],
                'is_available' => $product->is_available,
                'category_name' => $product->category?->name,
            ];
        }, $products->items());

        if ($this->page === 1) {
            $this->products = $newProducts;
        } else {
            $this->products = array_merge($this->products, $newProducts);
        }
    }

    private function calculateMinPrice(Product $product): int
    {
        $basePrice = (int) $product->price;
        $modifiers = $product->modifiers;

        if (! $modifiers || $modifiers->isEmpty()) {
            return $basePrice;
        }

        $minModifiersPrice = $modifiers
            ->where('type', 'single')
            ->min('price') ?? 0;

        return $basePrice + $minModifiersPrice;
    }

    public function loadMore(): void
    {
        if (! $this->hasMorePages) {
            return;
        }

        $this->page++;
        $this->loadProducts();
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->page = 1;
        $this->products = [];
        $this->hasMorePages = true;
        $this->loadProducts();
    }

    public function openModifierModal(string $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $this->selectedProductId = $productId;
        $this->selectedProductPrice = (int) $product->price;
        $this->selectedProductName = $product->name;
        $this->selectedModifiers = [];
        $this->selectedQuantity = 1;
    }

    public function closeModifierModal(): void
    {
        $this->selectedProductId = '';
        $this->selectedModifiers = [];
        $this->selectedQuantity = 1;
    }

    public function addToCart(): void
    {
        if (! $this->selectedProductId) {
            return;
        }

        $totalPrice = $this->calculateTotalPrice();

        $this->dispatch('cart-add-item', [
            'productId' => $this->selectedProductId,
            'vendorId' => $this->vendorId,
            'price' => $totalPrice,
            'productName' => $this->selectedProductName,
            'modifiers' => $this->selectedModifiers,
            'quantity' => $this->selectedQuantity,
        ]);

        $this->closeModifierModal();
        $this->dispatch('open-cart');
    }

    public function calculateTotalPrice(): int
    {
        $price = $this->selectedProductPrice;

        foreach ($this->selectedModifiers as $modifier) {
            if (isset($modifier['price'])) {
                $price += (int) $modifier['price'];
            }
        }

        return $price * $this->selectedQuantity;
    }

    public function render()
    {
        return view('livewire.menu.catalog');
    }
}
