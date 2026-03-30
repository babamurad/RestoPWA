import { Header } from '@/components/Header';
import { OrderCard, OrderCardSkeleton } from '@/components/OrderCard';
import { orderHistory } from '@/data/mockData';
import { useState, useEffect } from 'react';

interface OrdersPageProps {
  onBack: () => void;
}

export function OrdersPage({ onBack }: OrdersPageProps) {
  const [isLoading, setIsLoading] = useState(true);
  const [activeFilter, setActiveFilter] = useState<'all' | 'active' | 'completed'>('all');

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 800);
    return () => clearTimeout(timer);
  }, []);

  const filteredOrders = orderHistory.filter((order) => {
    if (activeFilter === 'active') {
      return ['pending', 'confirmed', 'preparing', 'delivering'].includes(order.status);
    }
    if (activeFilter === 'completed') {
      return order.status === 'delivered';
    }
    return true;
  });

  return (
    <div className="min-h-screen pb-20 bg-gray-50">
      <Header showBack onBack={onBack} title="Мои заказы" />

      {/* Filters */}
      <div className="px-4 py-3 bg-white border-b border-gray-100">
        <div className="flex gap-2">
          {[
            { id: 'all', label: 'Все' },
            { id: 'active', label: 'Активные' },
            { id: 'completed', label: 'Завершённые' },
          ].map((filter) => (
            <button
              key={filter.id}
              onClick={() => setActiveFilter(filter.id as typeof activeFilter)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 ${
                activeFilter === filter.id
                  ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {filter.label}
            </button>
          ))}
        </div>
      </div>

      {/* Orders List */}
      <main className="px-4 py-4">
        <div className="space-y-3">
          {isLoading
            ? Array.from({ length: 4 }).map((_, i) => (
                <OrderCardSkeleton key={i} />
              ))
            : filteredOrders.length > 0
            ? filteredOrders.map((order) => (
                <OrderCard key={order.id} order={order} />
              ))
            : (
              <div className="text-center py-12">
                <p className="text-gray-500">Заказы не найдены</p>
              </div>
            )}
        </div>
      </main>
    </div>
  );
}
