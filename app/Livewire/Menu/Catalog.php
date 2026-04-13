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
                'price' => (int) round($product->price * 100),
                'min_price' => $minPrice,
                'description' => $product->description,
                'image' => $product->image_url,
                'modifiers' => $product->modifiers ? $product->modifiers->toArray() : [],
                'is_available' => $product->is_available,
                'weight_g' => $product->weight_g,
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
        $basePrice = (int) round($product->price * 100);
        $modifiers = $product->modifiers;

        if (! $modifiers || $modifiers->isEmpty()) {
            return $basePrice;
        }

        $minModifiersPrice = $modifiers
            ->where('type', 'single')
            ->min('price') ?? 0;

        return $basePrice + (int) $minModifiersPrice;
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



    public function addDirectlyToCart(string $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $minPrice = $this->calculateMinPrice($product);

        $productImage = '';
        foreach ($this->products as $p) {
            if ($p['id'] === $productId) {
                $productImage = $p['image'];
                break;
            }
        }
        
        if (empty($productImage) && $product->image_url) {
            $productImage = $product->image_url;
        }

        $this->dispatch('cart-add-item',
            productId: $productId,
            vendorId: $this->vendorId,
            price: $minPrice,
            productName: $product->name,
            image: $productImage,
            modifiers: [],
            quantity: 1
        );
    }

    public function render()
    {
        return view('livewire.menu.catalog');
    }
}
