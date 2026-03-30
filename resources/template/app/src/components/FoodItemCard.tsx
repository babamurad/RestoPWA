import { Plus, Minus, Flame } from 'lucide-react';
import type { FoodItem } from '@/types';

interface FoodItemCardProps {
  item: FoodItem;
  quantity?: number;
  onAdd: () => void;
  onRemove?: () => void;
}

export function FoodItemCard({ item, quantity = 0, onAdd, onRemove }: FoodItemCardProps) {
  return (
    <div className="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100">
      <div className="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl">
        <img
          src={item.image}
          alt={item.name}
          className="w-full h-full object-cover"
          loading="lazy"
        />
        {item.isPopular && (
          <div className="absolute top-1 left-1 flex items-center gap-1 px-1.5 py-0.5 bg-orange-500 text-white text-[10px] font-semibold rounded-full">
            <Flame size={10} />
            Хит
          </div>
        )}
      </div>
      <div className="flex flex-col flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <div>
            <h4 className="font-semibold text-gray-900 line-clamp-1">{item.name}</h4>
            {item.weight && (
              <span className="text-xs text-gray-400">{item.weight}</span>
            )}
          </div>
        </div>
        <p className="mt-1 text-sm text-gray-500 line-clamp-2">{item.description}</p>
        <div className="flex items-center justify-between mt-auto pt-2">
          <span className="font-bold text-gray-900">{item.price} ₽</span>
          
          {quantity > 0 ? (
            <div className="flex items-center gap-2">
              <button
                onClick={onRemove}
                className="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors touch-feedback"
              >
                <Minus size={16} />
              </button>
              <span className="w-6 text-center font-semibold">{quantity}</span>
              <button
                onClick={onAdd}
                className="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 transition-colors touch-feedback"
              >
                <Plus size={16} />
              </button>
            </div>
          ) : (
            <button
              onClick={onAdd}
              className="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 transition-colors touch-feedback"
            >
              <Plus size={18} />
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

export function FoodItemCardSkeleton() {
  return (
    <div className="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100">
      <div className="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-xl animate-shimmer" />
      <div className="flex flex-col flex-1 space-y-2">
        <div className="w-2/3 h-5 bg-gray-200 rounded animate-shimmer" />
        <div className="w-full h-4 bg-gray-200 rounded animate-shimmer" />
        <div className="w-1/2 h-4 bg-gray-200 rounded animate-shimmer" />
        <div className="flex items-center justify-between mt-auto">
          <div className="w-16 h-5 bg-gray-200 rounded animate-shimmer" />
          <div className="w-8 h-8 bg-gray-200 rounded-full animate-shimmer" />
        </div>
      </div>
    </div>
  );
}
