import { useState, useEffect } from 'react';
import { Header, LocationHeader } from '@/components/Header';
import { CategoryChip, CategoryChipSkeleton } from '@/components/CategoryChip';
import { RestaurantCard, RestaurantCardSkeleton } from '@/components/RestaurantCard';
import { categories, restaurants, currentUser } from '@/data/mockData';
import type { Restaurant } from '@/types';

interface HomePageProps {
  onRestaurantClick: (restaurant: Restaurant) => void;
}

export function HomePage({ onRestaurantClick }: HomePageProps) {
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    // Simulate loading
    const timer = setTimeout(() => setIsLoading(false), 1500);
    return () => clearTimeout(timer);
  }, []);

  const filteredRestaurants = restaurants.filter((restaurant) => {
    const matchesCategory = activeCategory
      ? restaurant.categories.includes(activeCategory)
      : true;
    const matchesSearch = searchQuery
      ? restaurant.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        restaurant.categories.some((cat) =>
          cat.toLowerCase().includes(searchQuery.toLowerCase())
        )
      : true;
    return matchesCategory && matchesSearch;
  });

  const popularRestaurants = restaurants
    .sort((a, b) => b.rating - a.rating)
    .slice(0, 5);

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      <Header 
        onSearchClick={() => {
          const query = prompt('Введите поисковый запрос:');
          if (query) setSearchQuery(query);
        }}
      />
      <LocationHeader address={currentUser.addresses[0].address} />

      <main className="px-4 py-4 space-y-6">
        {/* Categories */}
        <section>
          <h2 className="text-lg font-semibold text-gray-900 mb-3">Категории</h2>
          <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
            {isLoading
              ? Array.from({ length: 6 }).map((_, i) => (
                  <CategoryChipSkeleton key={i} />
                ))
              : categories.map((category) => (
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
                ))}
          </div>
        </section>

        {/* Popular Restaurants */}
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

        {/* Nearby Restaurants */}
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
