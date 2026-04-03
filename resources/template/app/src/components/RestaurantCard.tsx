import { Star, Clock, MapPin } from 'lucide-react';
import type { Restaurant } from '@/types';

interface RestaurantCardProps {
  restaurant: Restaurant;
  onClick: () => void;
  variant?: 'horizontal' | 'vertical';
}

export function RestaurantCard({ restaurant, onClick, variant = 'vertical' }: RestaurantCardProps) {
  if (variant === 'horizontal') {
    return (
      <div
        onClick={onClick}
        className="flex-shrink-0 w-72 bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback"
      >
        <div className="relative h-36 overflow-hidden">
          <img
            src={restaurant.image}
            alt={restaurant.name}
            className="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
            loading="lazy"
          />
          {(restaurant.deliveryFee ?? 0) === 0 && (
            <div className="absolute top-3 left-3 px-2.5 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
              Бесплатная доставка
            </div>
          )}
        </div>
        <div className="p-4">
          <h3 className="font-semibold text-gray-900 truncate">{restaurant.name}</h3>
          <div className="flex items-center gap-3 mt-2 text-sm text-gray-500">
            <div className="flex items-center gap-1">
              <Star size={14} className="text-yellow-400 fill-yellow-400" />
              <span className="font-medium text-gray-700">{restaurant.rating}</span>
              <span>({restaurant.reviewCount})</span>
            </div>
            <div className="flex items-center gap-1">
              <Clock size={14} />
              <span>{restaurant.deliveryTime}</span>
            </div>
          </div>
          <div className="flex flex-wrap gap-1 mt-2">
            {(restaurant.categories || []).slice(0, 3).map((cat) => (
              <span key={cat} className="text-xs text-gray-500">
                {cat}
              </span>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div
      onClick={onClick}
      className="flex gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback"
    >
      <div className="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl">
        <img
          src={restaurant.image}
          alt={restaurant.name}
          className="w-full h-full object-cover"
          loading="lazy"
        />
      </div>
      <div className="flex flex-col justify-center flex-1 min-w-0">
        <h3 className="font-semibold text-gray-900 truncate">{restaurant.name}</h3>
        <div className="flex items-center gap-3 mt-1 text-sm text-gray-500">
          <div className="flex items-center gap-1">
            <Star size={14} className="text-yellow-400 fill-yellow-400" />
            <span className="font-medium text-gray-700">{restaurant.rating}</span>
          </div>
          <div className="flex items-center gap-1">
            <Clock size={14} />
            <span>{restaurant.deliveryTime}</span>
          </div>
        </div>
        <div className="flex items-center gap-1 mt-1 text-sm text-gray-500">
          <MapPin size={14} />
          <span className="truncate">{(restaurant.categories || []).join(' • ')}</span>
        </div>
        {(restaurant.deliveryFee ?? 0) === 0 ? (
          <span className="mt-2 text-xs font-medium text-green-600">Бесплатная доставка</span>
        ) : (
          <span className="mt-2 text-xs text-gray-500">Доставка {restaurant.deliveryFee ?? 0} ₽</span>
        )}
      </div>
    </div>
  );
}

export function RestaurantCardSkeleton({ variant = 'vertical' }: { variant?: 'horizontal' | 'vertical' }) {
  if (variant === 'horizontal') {
    return (
      <div className="flex-shrink-0 w-72 bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
        <div className="h-36 bg-gray-200 animate-shimmer" />
        <div className="p-4 space-y-3">
          <div className="w-3/4 h-5 bg-gray-200 rounded animate-shimmer" />
          <div className="flex gap-3">
            <div className="w-16 h-4 bg-gray-200 rounded animate-shimmer" />
            <div className="w-20 h-4 bg-gray-200 rounded animate-shimmer" />
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100">
      <div className="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-xl animate-shimmer" />
      <div className="flex flex-col justify-center flex-1 space-y-2">
        <div className="w-3/4 h-5 bg-gray-200 rounded animate-shimmer" />
        <div className="w-1/2 h-4 bg-gray-200 rounded animate-shimmer" />
        <div className="w-2/3 h-4 bg-gray-200 rounded animate-shimmer" />
      </div>
    </div>
  );
}
