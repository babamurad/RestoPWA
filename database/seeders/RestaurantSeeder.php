<?php

namespace Database\Seeders;

use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $tenantContext = app(TenantContext::class);

        $owner = \App\Models\User::firstOrCreate(
            ['email' => 'owner@restopwa.local'],
            [
                'name' => 'Restaurant Owner',
                'password' => bcrypt('password'),
            ]
        );

        $restaurants = $this->getRestaurantsData();

        foreach ($restaurants as $restaurantData) {
            $menuItems = $restaurantData['menu'];
            unset($restaurantData['menu']);

            $restaurantData['slug'] = \Illuminate\Support\Str::slug($restaurantData['name']);
            $restaurantData['owner_id'] = $owner->id;

            $existing = Restaurant::withoutGlobalScopes()->where('slug', $restaurantData['slug'])->first();
            if ($existing) {
                continue;
            }

            $tenantContext->setCurrentVendor(null);

            $restaurant = Restaurant::create($restaurantData);
            $tenantContext->setCurrentVendor($restaurant->id);

            $categoriesMap = $this->createCategories($restaurant->id, $menuItems);

            $this->createProducts($restaurant->id, $menuItems, $categoriesMap);
        }

        $tenantContext->setCurrentVendor(null);
    }

    private function getRestaurantsData(): array
    {
        return [
            [
                'name' => 'Pepperoni Pizza',
                'description' => 'Аутентичная итальянская пицца из дровяной печи. Тонкое тесто, свежие ингредиенты.',
                'image' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&h=400&fit=crop',
                'rating' => 4.8,
                'review_count' => 1247,
                'delivery_time' => '25-35 мин',
                'delivery_fee' => 0,
                'min_order' => 800,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Пепперони', 'description' => 'Томатный соус, моцарелла, пепперони, орегано', 'price' => 649, 'image' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=200&h=200&fit=crop', 'category' => 'Пицца', 'weight' => '450 г', 'is_popular' => true],
                    ['name' => 'Маргарита', 'description' => 'Томатный соус, моцарелла, свежий базилик', 'price' => 499, 'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=200&h=200&fit=crop', 'category' => 'Пицца', 'weight' => '400 г'],
                    ['name' => 'Четыре сыра', 'description' => 'Моцарелла, пармезан, горгонзола, чеддер', 'price' => 749, 'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=200&h=200&fit=crop', 'category' => 'Пицца', 'weight' => '480 г', 'is_popular' => true],
                    ['name' => 'Карбонара', 'description' => 'Спагетти, бекон, яйцо, пармезан, черный перец', 'price' => 549, 'image' => 'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=200&h=200&fit=crop', 'category' => 'Паста', 'weight' => '350 г'],
                    ['name' => 'Цезарь с курицей', 'description' => 'Салат айсберг, курица, пармезан, гренки, соус цезарь', 'price' => 449, 'image' => 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?w=200&h=200&fit=crop', 'category' => 'Салаты', 'weight' => '280 г'],
                ],
            ],
            [
                'name' => 'Burger House',
                'description' => 'Сочные бургеры из мраморной говядины. Ручная работа, свежие булочки.',
                'image' => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800&h=400&fit=crop',
                'rating' => 4.6,
                'review_count' => 892,
                'delivery_time' => '30-40 мин',
                'delivery_fee' => 99,
                'min_order' => 600,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Классический бургер', 'description' => 'Говяжья котлета, чеддер, салат, помидор, лук, соус', 'price' => 499, 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=200&fit=crop', 'category' => 'Бургеры', 'weight' => '320 г', 'is_popular' => true],
                    ['name' => 'Двойной чизбургер', 'description' => 'Две говяжьи котлеты, двойной чеддер, бекон, соус', 'price' => 699, 'image' => 'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=200&h=200&fit=crop', 'category' => 'Бургеры', 'weight' => '450 г', 'is_popular' => true],
                    ['name' => 'Картофель фри', 'description' => 'Хрустящий картофель фри с солью', 'price' => 249, 'image' => 'https://images.unsplash.com/photo-1630384060421-cb20d0e0649d?w=200&h=200&fit=crop', 'category' => 'Закуски', 'weight' => '200 г'],
                    ['name' => 'Куриные крылья', 'description' => 'Острые куриные крылья в соусе баффало', 'price' => 399, 'image' => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=200&h=200&fit=crop', 'category' => 'Закуски', 'weight' => '300 г'],
                ],
            ],
            [
                'name' => 'Sakura Sushi',
                'description' => 'Аутентичная японская кухня. Свежайшая рыба, мастера-сушисты.',
                'image' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1553621042-f6e147245754?w=800&h=400&fit=crop',
                'rating' => 4.9,
                'review_count' => 2156,
                'delivery_time' => '40-50 мин',
                'delivery_fee' => 149,
                'min_order' => 1000,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Филадельфия', 'description' => 'Лосось, сливочный сыр, огурец, авокадо', 'price' => 599, 'image' => 'https://images.unsplash.com/photo-1559410545-0bdcd187e0a6?w=200&h=200&fit=crop', 'category' => 'Роллы', 'weight' => '280 г', 'is_popular' => true],
                    ['name' => 'Калифорния', 'description' => 'Краб, авокадо, огурец, икра тобико', 'price' => 549, 'image' => 'https://images.unsplash.com/photo-1617196034796-73dfa7b1fd56?w=200&h=200&fit=crop', 'category' => 'Роллы', 'weight' => '260 г'],
                    ['name' => 'Сет "Сакура"', 'description' => '12 видов роллов, 48 штук', 'price' => 2499, 'image' => 'https://images.unsplash.com/photo-1553621042-f6e147245754?w=200&h=200&fit=crop', 'category' => 'Сеты', 'weight' => '1.2 кг', 'is_popular' => true],
                    ['name' => 'Мисо суп', 'description' => 'Традиционный японский суп с тофу и водорослями', 'price' => 199, 'image' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=200&h=200&fit=crop', 'category' => 'Супы', 'weight' => '300 мл'],
                ],
            ],
            [
                'name' => 'Green Salad',
                'description' => 'Полезные и вкусные салаты. Свежие овощи, качественные ингредиенты.',
                'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=800&h=400&fit=crop',
                'rating' => 4.5,
                'review_count' => 567,
                'delivery_time' => '20-30 мин',
                'delivery_fee' => 0,
                'min_order' => 500,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Греческий салат', 'description' => 'Помидоры, огурцы, перец, оливки, фета, оливковое масло', 'price' => 399, 'image' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=200&h=200&fit=crop', 'category' => 'Салаты', 'weight' => '300 г'],
                    ['name' => 'Салат с авокадо', 'description' => 'Авокадо, шпинат, киноа, гранат, лимонная заправка', 'price' => 459, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=200&h=200&fit=crop', 'category' => 'Салаты', 'weight' => '280 г', 'is_popular' => true],
                    ['name' => 'Смузи боул', 'description' => 'Асаи, банан, ягоды, гранола, кокосовые чипсы', 'price' => 499, 'image' => 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=200&h=200&fit=crop', 'category' => 'Боулы', 'weight' => '350 г'],
                ],
            ],
            [
                'name' => 'Sweet Dreams',
                'description' => 'Десерты ручной работы. Торты, пирожные, мороженое.',
                'image' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1558301211-0d8c8ddee6ec?w=800&h=400&fit=crop',
                'rating' => 4.7,
                'review_count' => 743,
                'delivery_time' => '25-35 мин',
                'delivery_fee' => 99,
                'min_order' => 400,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Тирамису', 'description' => 'Классический итальянский десерт с маскарпоне и кофе', 'price' => 349, 'image' => 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=200&h=200&fit=crop', 'category' => 'Десерты', 'weight' => '150 г', 'is_popular' => true],
                    ['name' => 'Чизкейк Нью-Йорк', 'description' => 'Классический чизкейк с ягодным соусом', 'price' => 399, 'image' => 'https://images.unsplash.com/photo-1524351199678-941a58a3df50?w=200&h=200&fit=crop', 'category' => 'Десерты', 'weight' => '180 г'],
                    ['name' => 'Макаруны (набор)', 'description' => '6 макарун разных вкусов', 'price' => 599, 'image' => 'https://images.unsplash.com/photo-1569864358642-9d1684040f43?w=200&h=200&fit=crop', 'category' => 'Десерты', 'weight' => '120 г'],
                ],
            ],
            [
                'name' => 'Coffee Time',
                'description' => 'Специалитет кофе и свежая выпечка. Идеальное место для завтрака.',
                'image' => 'https://images.unsplash.com/photo-1554118811-0d8c8ddee6ec?w=400&h=300&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=400&fit=crop',
                'rating' => 4.4,
                'review_count' => 428,
                'delivery_time' => '15-25 мин',
                'delivery_fee' => 0,
                'min_order' => 300,
                'is_active' => true,
                'menu' => [
                    ['name' => 'Капучино', 'description' => 'Эспрессо с молочной пенкой', 'price' => 249, 'image' => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=200&h=200&fit=crop', 'category' => 'Кофе', 'weight' => '250 мл'],
                    ['name' => 'Латте', 'description' => 'Эспрессо с молоком и нежной пенкой', 'price' => 279, 'image' => 'https://images.unsplash.com/photo-1570968992193-d6ea066f0e5c?w=200&h=200&fit=crop', 'category' => 'Кофе', 'weight' => '350 мл'],
                    ['name' => 'Круассан', 'description' => 'Свежий слоёный круассан', 'price' => 199, 'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=200&h=200&fit=crop', 'category' => 'Выпечка', 'weight' => '80 г'],
                ],
            ],
        ];
    }

    private function createCategories(string $vendorId, array $menuItems): array
    {
        $categoryNames = array_unique(array_column($menuItems, 'category'));
        $categoriesMap = [];

        foreach ($categoryNames as $index => $name) {
            $category = Category::create([
                'vendor_id' => $vendorId,
                'name' => $name,
                'sort_order' => $index,
            ]);
            $categoriesMap[$name] = $category->id;
        }

        return $categoriesMap;
    }

    private function createProducts(string $vendorId, array $menuItems, array $categoriesMap): void
    {
        foreach ($menuItems as $item) {
            $categoryId = $categoriesMap[$item['category']] ?? null;
            $weight = (int) preg_replace('/[^0-9]/', '', $item['weight']);

            Product::create([
                'vendor_id' => $vendorId,
                'category_id' => $categoryId,
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'image' => $item['image'] ?? null,
                'weight_g' => $weight ?: null,
                'is_available' => true,
            ]);
        }
    }
}
