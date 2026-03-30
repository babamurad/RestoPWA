import { ShoppingBag, ArrowRight } from 'lucide-react';
import { Header } from '@/components/Header';
import { CartItemCard, CartItemCardSkeleton } from '@/components/CartItemCard';
import type { CartItem } from '@/types';
import { useState, useEffect } from 'react';

interface CartPageProps {
  cartItems: CartItem[];
  onBack: () => void;
  onCheckout: () => void;
  onIncreaseQuantity: (itemId: string) => void;
  onDecreaseQuantity: (itemId: string) => void;
  onRemoveItem: (itemId: string) => void;
}

export function CartPage({
  cartItems,
  onBack,
  onCheckout,
  onIncreaseQuantity,
  onDecreaseQuantity,
  onRemoveItem,
}: CartPageProps) {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 500);
    return () => clearTimeout(timer);
  }, []);

  const totalAmount = cartItems.reduce(
    (sum, item) => sum + item.foodItem.price * item.quantity,
    0
  );

  const totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0);

  if (cartItems.length === 0 && !isLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header showBack onBack={onBack} title="Корзина" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <div className="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mb-4">
            <ShoppingBag size={40} className="text-orange-500" />
          </div>
          <h2 className="text-xl font-semibold text-gray-900">Корзина пуста</h2>
          <p className="text-gray-500 text-center mt-2 mb-6">
            Добавьте блюда из ресторанов, чтобы оформить заказ
          </p>
          <button
            onClick={onBack}
            className="px-6 py-3 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors touch-feedback"
          >
            Перейти к ресторанам
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen pb-32 bg-gray-50">
      <Header showBack onBack={onBack} title="Корзина" />

      <main className="px-4 py-4">
        {/* Restaurant Info */}
        {cartItems.length > 0 && (
          <div className="mb-4 p-3 bg-white rounded-xl border border-gray-100">
            <span className="text-sm text-gray-500">Ресторан</span>
            <p className="font-semibold text-gray-900">{cartItems[0].restaurantName}</p>
          </div>
        )}

        {/* Cart Items */}
        <div className="space-y-3">
          {isLoading
            ? Array.from({ length: 3 }).map((_, i) => (
                <CartItemCardSkeleton key={i} />
              ))
            : cartItems.map((item) => (
                <CartItemCard
                  key={item.foodItem.id}
                  item={item}
                  onIncrease={() => onIncreaseQuantity(item.foodItem.id)}
                  onDecrease={() => onDecreaseQuantity(item.foodItem.id)}
                  onRemove={() => onRemoveItem(item.foodItem.id)}
                />
              ))}
        </div>
      </main>

      {/* Checkout Bar */}
      <div className="fixed bottom-16 left-0 right-0 bg-white border-t border-gray-100 p-4 safe-bottom">
        <div className="max-w-lg mx-auto">
          <div className="flex items-center justify-between mb-3">
            <span className="text-gray-600">
              {totalItems} {totalItems === 1 ? 'товар' : totalItems < 5 ? 'товара' : 'товаров'}
            </span>
            <span className="text-xl font-bold text-gray-900">{totalAmount} ₽</span>
          </div>
          <button
            onClick={onCheckout}
            className="w-full flex items-center justify-center gap-2 px-6 py-4 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors touch-feedback btn-press"
          >
            <span>Оформить заказ</span>
            <ArrowRight size={20} />
          </button>
        </div>
      </div>
    </div>
  );
}
