import { Home, ShoppingBag, ShoppingCart, User } from 'lucide-react';
import type { Page } from '@/types';

interface BottomNavProps {
  currentPage: Page;
  onPageChange: (page: Page) => void;
  cartItemCount: number;
}

export function BottomNav({ currentPage, onPageChange, cartItemCount }: BottomNavProps) {
  const navItems: { id: Page; label: string; icon: typeof Home; badge?: number }[] = [
    { id: 'home', label: 'Главная', icon: Home },
    { id: 'orders', label: 'Заказы', icon: ShoppingBag },
    { id: 'cart', label: 'Корзина', icon: ShoppingCart, badge: cartItemCount > 0 ? cartItemCount : undefined },
    { id: 'profile', label: 'Профиль', icon: User },
  ];

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 safe-bottom">
      <div className="flex items-center justify-around h-16 max-w-lg mx-auto">
        {navItems.map((item) => {
          const isActive = currentPage === item.id;
          const Icon = item.icon;
          
          return (
            <button
              key={item.id}
              onClick={() => onPageChange(item.id)}
              className={`relative flex flex-col items-center justify-center w-full h-full transition-colors duration-200 ${
                isActive ? 'text-orange-500' : 'text-gray-400 hover:text-gray-600'
              }`}
            >
              <div className="relative">
                <Icon 
                  size={24} 
                  strokeWidth={isActive ? 2.5 : 2}
                  className={`transition-transform duration-200 ${isActive ? 'scale-110' : ''}`}
                />
                {item.badge && (
                  <span className="absolute -top-2 -right-2 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-semibold text-white bg-orange-500 rounded-full">
                    {item.badge > 99 ? '99+' : item.badge}
                  </span>
                )}
              </div>
              <span className={`mt-1 text-xs font-medium ${isActive ? 'text-orange-500' : ''}`}>
                {item.label}
              </span>
              {isActive && (
                <div className="absolute top-0 w-12 h-0.5 bg-orange-500 rounded-b-full" />
              )}
            </button>
          );
        })}
      </div>
    </nav>
  );
}
