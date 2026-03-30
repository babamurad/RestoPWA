import { MapPin, CreditCard, Bell, Settings, ChevronRight, Star, Phone, Mail } from 'lucide-react';
import { currentUser } from '@/data/mockData';
import { OrderCard, OrderCardSkeleton } from '@/components/OrderCard';
import { orderHistory } from '@/data/mockData';
import { useState, useEffect } from 'react';

interface ProfilePageProps {
  onViewAllOrders: () => void;
}

export function ProfilePage({ onViewAllOrders }: ProfilePageProps) {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 800);
    return () => clearTimeout(timer);
  }, []);

  const menuItems = [
    { icon: MapPin, label: 'Мои адреса', count: currentUser.addresses.length },
    { icon: CreditCard, label: 'Способы оплаты', count: currentUser.paymentMethods.length },
    { icon: Bell, label: 'Уведомления', badge: 3 },
    { icon: Settings, label: 'Настройки' },
  ];

  const recentOrders = orderHistory.slice(0, 3);

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      {/* Profile Header */}
      <div className="bg-white border-b border-gray-100">
        <div className="px-4 py-6">
          <div className="flex items-center gap-4">
            <div className="relative">
              <img
                src={currentUser.avatar}
                alt={currentUser.name}
                className="w-20 h-20 rounded-full object-cover border-4 border-orange-100"
              />
              <button className="absolute bottom-0 right-0 w-7 h-7 bg-orange-500 text-white rounded-full flex items-center justify-center shadow-lg">
                <Settings size={14} />
              </button>
            </div>
            <div className="flex-1">
              <h1 className="text-xl font-bold text-gray-900">{currentUser.name}</h1>
              <div className="flex items-center gap-3 mt-1 text-sm text-gray-500">
                <span className="flex items-center gap-1">
                  <Phone size={14} />
                  {currentUser.phone}
                </span>
              </div>
              <div className="flex items-center gap-1 mt-1 text-sm text-gray-500">
                <Mail size={14} />
                {currentUser.email}
              </div>
            </div>
          </div>
        </div>
      </div>

      <main className="px-4 py-4 space-y-4">
        {/* Stats */}
        <div className="grid grid-cols-3 gap-3">
          <div className="bg-white p-4 rounded-2xl border border-gray-100 text-center">
            <div className="w-10 h-10 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-2">
              <Star size={20} className="text-orange-500" />
            </div>
            <p className="text-2xl font-bold text-gray-900">4.8</p>
            <p className="text-xs text-gray-500">Рейтинг</p>
          </div>
          <div className="bg-white p-4 rounded-2xl border border-gray-100 text-center">
            <div className="w-10 h-10 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-2">
              <MapPin size={20} className="text-green-500" />
            </div>
            <p className="text-2xl font-bold text-gray-900">{orderHistory.length}</p>
            <p className="text-xs text-gray-500">Заказов</p>
          </div>
          <div className="bg-white p-4 rounded-2xl border border-gray-100 text-center">
            <div className="w-10 h-10 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-2">
              <CreditCard size={20} className="text-blue-500" />
            </div>
            <p className="text-2xl font-bold text-gray-900">{currentUser.paymentMethods.length}</p>
            <p className="text-xs text-gray-500">Карт</p>
          </div>
        </div>

        {/* Menu Items */}
        <section className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
          {menuItems.map((item, index) => {
            const Icon = item.icon;
            return (
              <button
                key={item.label}
                className={`w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors ${
                  index !== menuItems.length - 1 ? 'border-b border-gray-100' : ''
                }`}
              >
                <div className="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">
                  <Icon size={20} className="text-gray-600" />
                </div>
                <span className="flex-1 text-left font-medium text-gray-900">{item.label}</span>
                {item.count !== undefined && (
                  <span className="text-sm text-gray-500">{item.count}</span>
                )}
                {item.badge && (
                  <span className="px-2 py-0.5 bg-orange-500 text-white text-xs font-semibold rounded-full">
                    {item.badge}
                  </span>
                )}
                <ChevronRight size={18} className="text-gray-400" />
              </button>
            );
          })}
        </section>

        {/* Recent Orders */}
        <section>
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-lg font-semibold text-gray-900">История заказов</h2>
            <button
              onClick={onViewAllOrders}
              className="text-sm text-orange-500 font-medium hover:text-orange-600"
            >
              Все
            </button>
          </div>
          <div className="space-y-3">
            {isLoading
              ? Array.from({ length: 2 }).map((_, i) => (
                  <OrderCardSkeleton key={i} />
                ))
              : recentOrders.map((order) => (
                  <OrderCard key={order.id} order={order} />
                ))}
          </div>
        </section>

        {/* Logout */}
        <button className="w-full py-3 text-red-500 font-medium hover:bg-red-50 rounded-xl transition-colors">
          Выйти из аккаунта
        </button>
      </main>
    </div>
  );
}
