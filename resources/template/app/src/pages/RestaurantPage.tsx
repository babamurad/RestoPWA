import { useState, useEffect } from 'react';
import { Star, Clock, MapPin, Info } from 'lucide-react';
import { Header } from '@/components/Header';
import { FoodItemCard, FoodItemCardSkeleton } from '@/components/FoodItemCard';
import { fetchMenu } from '@/services/api';
import type { Restaurant, FoodItem, CartItem, MenuCategory } from '@/types';

interface RestaurantPageProps {
  restaurant: Restaurant;
  cartItems: CartItem[];
  onBack: () => void;
  onAddToCart: (item: FoodItem, restaurant: Restaurant) => void;
  onRemoveFromCart: (itemId: string) => void;
}

interface MenuData {
  categories: MenuCategory[];
  products: {
    data: FoodItem[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
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
  const [menuData, setMenuData] = useState<MenuData | null>(null);
  const [error, setError] = useState<string | null>(null);

  const vendorSlug = restaurant.slug || restaurant.id;

  useEffect(() => {
    loadMenu();
  }, [vendorSlug]);

  const loadMenu = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await fetchMenu(vendorSlug);
      
      const mappedProducts = data.products.data.map((p: FoodItem) => ({
        ...p,
        image: p.image_url || p.image,
        weight: p.weight_g ? `${p.weight_g} г` : undefined,
        category: p.category?.name || p.category,
      }));

      setMenuData({
        categories: data.categories,
        products: {
          ...data.products,
          data: mappedProducts,
        },
      });
    } catch (err) {
      console.error('Failed to load menu:', err);
      setError('Не удалось загрузить меню');
      setMenuData({
        categories: [],
        products: {
          data: restaurant.menu || [],
          meta: { current_page: 1, last_page: 1, per_page: 50, total: restaurant.menu?.length || 0 },
        },
      });
    } finally {
      setIsLoading(false);
    }
  };

  const menuCategories = menuData?.categories.length 
    ? ['all', ...menuData.categories.map((c) => c.name)]
    : ['all'];

  const filteredMenu = activeCategory === 'all'
    ? menuData?.products.data || []
    : (menuData?.products.data || []).filter(
        (item) => item.category === activeCategory
      );

  const getItemQuantity = (itemId: string) => {
    const cartItem = cartItems.find((item) => item.foodItem.id === itemId);
    return cartItem?.quantity || 0;
  };

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      <Header showBack onBack={onBack} />

      <div className="relative h-48 overflow-hidden">
        <img
          src={restaurant.coverImage || restaurant.image || '/images/restaurant-cover-placeholder.jpg'}
          alt={restaurant.name}
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
      </div>

      <div className="px-4 -mt-6 relative z-10">
        <div className="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div className="flex items-start justify-between">
            <div>
              <h1 className="text-xl font-bold text-gray-900">{restaurant.name}</h1>
              {restaurant.categories && (
                <p className="text-sm text-gray-500 mt-1">
                  {restaurant.categories.join(' • ')}
                </p>
              )}
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
              <span className="font-semibold text-gray-900">{restaurant.rating || '—'}</span>
              {restaurant.reviewCount && (
                <span className="text-gray-500">({restaurant.reviewCount})</span>
              )}
            </div>
            <div className="flex items-center gap-1 text-gray-500">
              <Clock size={16} />
              <span>{restaurant.deliveryTime || restaurant.delivery_time || '30-40 мин'}</span>
            </div>
            <div className="flex items-center gap-1 text-gray-500">
              <MapPin size={16} />
              <span>{restaurant.deliveryFee === 0 ? 'Бесплатно' : `${restaurant.deliveryFee} ₽`}</span>
            </div>
          </div>

          {showInfo && (
            <div className="mt-4 pt-4 border-t border-gray-100 animate-slide-up">
              <p className="text-sm text-gray-600">{restaurant.description || 'Описание ресторана'}</p>
              <div className="mt-3 flex items-center gap-2 text-sm text-gray-500">
                <span>Минимальный заказ:</span>
                <span className="font-medium text-gray-900">{restaurant.minOrder || 500} ₽</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {error && (
        <div className="mx-4 mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {error}
          <button onClick={loadMenu} className="ml-2 underline">Повторить</button>
        </div>
      )}

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

      <div className="mt-4 px-4 pb-4">
        <h2 className="text-lg font-semibold text-gray-900 mb-3">
          {activeCategory === 'all' ? 'Все блюда' : activeCategory}
        </h2>
        <div className="space-y-3">
          {isLoading
            ? Array.from({ length: 4 }).map((_, i) => (
                <FoodItemCardSkeleton key={i} />
              ))
            : filteredMenu.length > 0
            ? filteredMenu.map((item) => (
                <FoodItemCard
                  key={item.id}
                  item={item}
                  quantity={getItemQuantity(item.id)}
                  onAdd={() => onAddToCart(item, restaurant)}
                  onRemove={() => onRemoveFromCart(item.id)}
                />
              ))
            : (
              <div className="text-center py-8 text-gray-500">
                В этой категории пока нет блюд
              </div>
            )}
        </div>
      </div>
    </div>
  );
}
