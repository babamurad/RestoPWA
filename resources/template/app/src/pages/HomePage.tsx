import { useState, useEffect, useCallback } from 'react';
import { Header, LocationHeader } from '@/components/Header';
import { CategoryChip, CategoryChipSkeleton } from '@/components/CategoryChip';
import { RestaurantCard, RestaurantCardSkeleton } from '@/components/RestaurantCard';
import { currentUser } from '@/data/mockData';
import { fetchRestaurants, fetchCategories } from '@/services/api';
import type { Restaurant, Category } from '@/types';

interface HomePageProps {
  onRestaurantClick: (restaurant: Restaurant) => void;
}

export function HomePage({ onRestaurantClick }: HomePageProps) {
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [restaurants, setRestaurants] = useState<Restaurant[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      const [restaurantsData, categoriesData] = await Promise.all([
        fetchRestaurants(),
        fetchCategories().catch(() => ({ data: [] })),
      ]);
      
      const mappedRestaurants = restaurantsData.map((r: Restaurant) => ({
        ...r,
        deliveryTime: r.deliveryTime || (r as any).delivery_time,
        deliveryFee: r.deliveryFee ?? (r as any).delivery_fee ?? 0,
        image: r.image || '/images/restaurant-placeholder.jpg',
        coverImage: r.coverImage || (r as any).cover_image,
        reviewCount: r.reviewCount || (r as any).review_count,
        minOrder: r.minOrder || (r as any).min_order,
        isOpen: (r as any).is_active ?? r.isOpen ?? true,
      }));
      setRestaurants(mappedRestaurants);

      const mappedCategories = categoriesData.data.map((c: any) => ({
        id: c.id.toString(),
        name: c.name,
      }));
      setCategories(mappedCategories.length > 0 ? mappedCategories : []);
    } catch (err) {
      console.error('Failed to load data:', err);
      setError('Не удалось загрузить данные');
    } finally {
      setIsLoading(false);
    }
  };

  const filteredRestaurants = restaurants.filter((restaurant) => {
    const matchesCategory = activeCategory
      ? restaurant.categories?.includes(activeCategory)
      : true;
    const matchesSearch = searchQuery
      ? restaurant.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        restaurant.categories?.some((cat) =>
          cat.toLowerCase().includes(searchQuery.toLowerCase())
        )
      : true;
    return matchesCategory && matchesSearch;
  });

  const popularRestaurants = [...restaurants]
    .sort((a, b) => (b.rating || 0) - (a.rating || 0))
    .slice(0, 5);

  const handleSearch = useCallback(() => {
    const query = prompt('Введите поисковый запрос:');
    if (query) setSearchQuery(query);
  }, []);

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      <Header onSearchClick={handleSearch} />
      <LocationHeader address={currentUser.addresses[0].address} />

      <main className="px-4 py-4 space-y-6">
        {error && (
          <div className="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            {error}
            <button onClick={loadData} className="ml-2 underline">Повторить</button>
          </div>
        )}

        <section>
          <h2 className="text-lg font-semibold text-gray-900 mb-3">Категории</h2>
          <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
            {isLoading
              ? Array.from({ length: 6 }).map((_, i) => (
                  <CategoryChipSkeleton key={i} />
                ))
              : categories.length > 0
              ? categories.map((category) => (
                  <CategoryChip
                    key={category.id}
                    name={category.name}
                    icon={category.icon}
                    isActive={activeCategory === category.name}
                    onClick={() =>
                      setActiveCategory(
                        activeCategory === category.name ? null : category.name
                      )
                    }
                  />
                ))
              : Array.from({ length: 6 }).map((_, i) => (
                  <CategoryChipSkeleton key={i} />
                ))}
          </div>
        </section>

        {!activeCategory && !searchQuery && (
          <section>
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-lg font-semibold text-gray-900">Популярные</h2>
              <button className="text-sm text-orange-500 font-medium hover:text-orange-600">
                Все
              </button>
            </div>
            <div className="flex gap-4 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
              {isLoading
                ? Array.from({ length: 4 }).map((_, i) => (
                    <RestaurantCardSkeleton key={i} variant="horizontal" />
                  ))
                : popularRestaurants.map((restaurant) => (
                    <RestaurantCard
                      key={restaurant.id}
                      restaurant={restaurant}
                      onClick={() => onRestaurantClick(restaurant)}
                      variant="horizontal"
                    />
                  ))}
            </div>
          </section>
        )}

        <section>
          <h2 className="text-lg font-semibold text-gray-900 mb-3">
            {searchQuery
              ? 'Результаты поиска'
              : activeCategory
              ? `${activeCategory} рядом с вами`
              : 'Рядом с вами'}
          </h2>
          <div className="space-y-3">
            {isLoading
              ? Array.from({ length: 4 }).map((_, i) => (
                  <RestaurantCardSkeleton key={i} variant="vertical" />
                ))
              : filteredRestaurants.length > 0
              ? filteredRestaurants.map((restaurant) => (
                  <RestaurantCard
                    key={restaurant.id}
                    restaurant={restaurant}
                    onClick={() => onRestaurantClick(restaurant)}
                    variant="vertical"
                  />
                ))
              : (
                <div className="text-center py-8">
                  <p className="text-gray-500">Ничего не найдено</p>
                  <button
                    onClick={() => {
                      setActiveCategory(null);
                      setSearchQuery('');
                    }}
                    className="mt-2 text-orange-500 font-medium"
                  >
                    Сбросить фильтры
                  </button>
                </div>
              )}
          </div>
        </section>
      </main>
    </div>
  );
}
