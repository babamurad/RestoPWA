import { Minus, Plus, Trash2 } from 'lucide-react';
import type { CartItem } from '@/types';

interface CartItemCardProps {
  item: CartItem;
  onIncrease: () => void;
  onDecrease: () => void;
  onRemove: () => void;
}

export function CartItemCard({ item, onIncrease, onDecrease, onRemove }: CartItemCardProps) {
  const totalPrice = item.foodItem.price * item.quantity;
  
  return (
    <div className="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100">
      <div className="flex-shrink-0 w-20 h-20 overflow-hidden rounded-xl">
        <img
          src={item.foodItem.image}
          alt={item.foodItem.name}
          className="w-full h-full object-cover"
          loading="lazy"
        />
      </div>
      <div className="flex flex-col flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <div>
            <h4 className="font-semibold text-gray-900 line-clamp-1">{item.foodItem.name}</h4>
            <span className="text-xs text-gray-400">{item.restaurantName}</span>
          </div>
          <button
            onClick={onRemove}
            className="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
          >
            <Trash2 size={16} />
          </button>
        </div>
        
        <div className="flex items-center justify-between mt-auto pt-2">
          <span className="font-bold text-gray-900">{totalPrice} ₽</span>
          
          <div className="flex items-center gap-2">
            <button
              onClick={onDecrease}
              className="flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors touch-feedback"
            >
              <Minus size={14} />
            </button>
            <span className="w-6 text-center font-semibold text-sm">{item.quantity}</span>
            <button
              onClick={onIncrease}
              className="flex items-center justify-center w-7 h-7 rounded-full bg-orange-500 text-white hover:bg-orange-600 transition-colors touch-feedback"
            >
              <Plus size={14} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export function CartItemCardSkeleton() {
  return (
    <div className="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100">
      <div className="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-xl animate-shimmer" />
      <div className="flex flex-col flex-1 space-y-2">
        <div className="flex justify-between">
          <div className="w-1/2 h-5 bg-gray-200 rounded animate-shimmer" />
          <div className="w-6 h-6 bg-gray-200 rounded animate-shimmer" />
        </div>
        <div className="w-1/3 h-3 bg-gray-200 rounded animate-shimmer" />
        <div className="flex items-center justify-between mt-auto">
          <div className="w-14 h-5 bg-gray-200 rounded animate-shimmer" />
          <div className="flex gap-2">
            <div className="w-7 h-7 bg-gray-200 rounded-full animate-shimmer" />
            <div className="w-7 h-7 bg-gray-200 rounded-full animate-shimmer" />
          </div>
        </div>
      </div>
    </div>
  );
}
