import { useState, useEffect } from 'react';
import { Star, Clock, MapPin, Info } from 'lucide-react';
import { Header } from '@/components/Header';
import { FoodItemCard, FoodItemCardSkeleton } from '@/components/FoodItemCard';
import type { Restaurant, FoodItem, CartItem } from '@/types';

interface RestaurantPageProps {
  restaurant: Restaurant;
  cartItems: CartItem[];
  onBack: () => void;
  onAddToCart: (item: FoodItem, restaurant: Restaurant) => void;
  onRemoveFromCart: (itemId: string) => void;
}

export function RestaurantPage({
  restaurant,
  cartItems,
  onBack,
  onAddToCart,
  onRemoveFromCart,
}: RestaurantPageProps) {
  const [activeCategory, setActiveCategory] = useState<string>('all');
  const [isLoading, setIsLoading] = useState(true);
  const [showInfo, setShowInfo] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 1000);
    return () => clearTimeout(timer);
  }, []);

  const menuCategories = ['all', ...new Set(restaurant.menu.map((item) => item.category))];

  const filteredMenu =
    activeCategory === 'all'
      ? restaurant.menu
      : restaurant.menu.filter((item) => item.category === activeCategory);

  const getItemQuantity = (itemId: string) => {
    const cartItem = cartItems.find((item) => item.foodItem.id === itemId);
    return cartItem?.quantity || 0;
  };

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      <Header showBack onBack={onBack} />

      {/* Cover Image */}
      <div className="relative h-48 overflow-hidden">
        <img
          src={restaurant.coverImage}
          alt={restaurant.name}
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
      </div>

      {/* Restaurant Info */}
      <div className="px-4 -mt-6 relative z-10">
        <div className="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div className="flex items-start justify-between">
            <div>
              <h1 className="text-xl font-bold text-gray-900">{restaurant.name}</h1>
              <p className="text-sm text-gray-500 mt-1">
                {restaurant.categories.join(' • ')}
              </p>
            </div>
            <button
              onClick={() => setShowInfo(!showInfo)}
              className="p-2 rounded-full hover:bg-gray-100 transition-colors"
            >
              <Info size={20} className="text-gray-400" />
            </button>
          </div>

          <div className="flex items-center gap-4 mt-3 text-sm">
            <div className="flex items-center gap-1">
              <Star size={16} className="text-yellow-400 fill-yellow-400" />
              <span className="font-semibold text-gray-900">{restaurant.rating}</span>
              <span className="text-gray-500">({restaurant.reviewCount})</span>
            </div>
            <div className="flex items-center gap-1 text-gray-500">
              <Clock size={16} />
              <span>{restaurant.deliveryTime}</span>
            </div>
            <div className="flex items-center gap-1 text-gray-500">
              <MapPin size={16} />
              <span>{restaurant.deliveryFee === 0 ? 'Бесплатно' : `${restaurant.deliveryFee} ₽`}</span>
            </div>
          </div>

          {showInfo && (
            <div className="mt-4 pt-4 border-t border-gray-100 animate-slide-up">
              <p className="text-sm text-gray-600">{restaurant.description}</p>
              <div className="mt-3 flex items-center gap-2 text-sm text-gray-500">
                <span>Минимальный заказ:</span>
                <span className="font-medium text-gray-900">{restaurant.minOrder} ₽</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Menu Categories */}
      <div className="mt-4 px-4">
        <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
          {menuCategories.map((category) => (
            <button
              key={category}
              onClick={() => setActiveCategory(category)}
              className={`px-4 py-2 rounded-full whitespace-nowrap text-sm font-medium transition-all duration-200 ${
                activeCategory === category
                  ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              }`}
            >
              {category === 'all' ? 'Все блюда' : category}
            </button>
          ))}
        </div>
      </div>

      {/* Menu Items */}
      <div className="mt-4 px-4 pb-4">
        <h2 className="text-lg font-semibold text-gray-900 mb-3">
          {activeCategory === 'all' ? 'Все блюда' : activeCategory}
        </h2>
        <div className="space-y-3">
          {isLoading
            ? Array.from({ length: 4 }).map((_, i) => (
                <FoodItemCardSkeleton key={i} />
              ))
            : filteredMenu.map((item) => (
                <FoodItemCard
                  key={item.id}
                  item={item}
                  quantity={getItemQuantity(item.id)}
                  onAdd={() => onAddToCart(item, restaurant)}
                  onRemove={() => onRemoveFromCart(item.id)}
                />
              ))}
        </div>
      </div>
    </div>
  );
}
