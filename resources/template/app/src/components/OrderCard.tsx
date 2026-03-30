import { ChevronRight, Package, CheckCircle, Clock, XCircle } from 'lucide-react';
import type { Order } from '@/types';

interface OrderCardProps {
  order: Order;
  onClick?: () => void;
}

const statusConfig: Record<string, { label: string; color: string; icon: typeof Package }> = {
  pending: { label: 'Ожидает подтверждения', color: 'text-yellow-600 bg-yellow-50', icon: Clock },
  confirmed: { label: 'Подтверждён', color: 'text-blue-600 bg-blue-50', icon: Package },
  preparing: { label: 'Готовится', color: 'text-orange-600 bg-orange-50', icon: Package },
  delivering: { label: 'В пути', color: 'text-purple-600 bg-purple-50', icon: Package },
  delivered: { label: 'Доставлен', color: 'text-green-600 bg-green-50', icon: CheckCircle },
  cancelled: { label: 'Отменён', color: 'text-red-600 bg-red-50', icon: XCircle },
};

export function OrderCard({ order, onClick }: OrderCardProps) {
  const status = statusConfig[order.status] || statusConfig.pending;
  const StatusIcon = status.icon;
  
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const totalItems = order.items.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <div
      onClick={onClick}
      className="p-4 bg-white rounded-2xl border border-gray-100 cursor-pointer card-hover touch-feedback"
    >
      <div className="flex items-start justify-between">
        <div>
          <div className="flex items-center gap-2">
            <span className="text-sm font-medium text-gray-500">{order.id}</span>
            <span className="text-xs text-gray-400">{formatDate(order.createdAt)}</span>
          </div>
          <h4 className="mt-1 font-semibold text-gray-900">{order.restaurantName}</h4>
        </div>
        <div className={`flex items-center gap-1 px-2 py-1 rounded-full ${status.color}`}>
          <StatusIcon size={14} />
          <span className="text-xs font-medium">{status.label}</span>
        </div>
      </div>
      
      <div className="mt-3 text-sm text-gray-600">
        {totalItems} {totalItems === 1 ? 'товар' : totalItems < 5 ? 'товара' : 'товаров'}
      </div>
      
      <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
        <span className="font-bold text-gray-900">{order.total} ₽</span>
        <div className="flex items-center gap-1 text-orange-500">
          <span className="text-sm font-medium">Подробнее</span>
          <ChevronRight size={16} />
        </div>
      </div>
    </div>
  );
}

export function OrderCardSkeleton() {
  return (
    <div className="p-4 bg-white rounded-2xl border border-gray-100">
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex gap-2">
            <div className="w-16 h-4 bg-gray-200 rounded animate-shimmer" />
            <div className="w-20 h-4 bg-gray-200 rounded animate-shimmer" />
          </div>
          <div className="w-32 h-5 bg-gray-200 rounded animate-shimmer" />
        </div>
        <div className="w-24 h-6 bg-gray-200 rounded-full animate-shimmer" />
      </div>
      <div className="mt-3 w-20 h-4 bg-gray-200 rounded animate-shimmer" />
      <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
        <div className="w-16 h-5 bg-gray-200 rounded animate-shimmer" />
        <div className="w-20 h-4 bg-gray-200 rounded animate-shimmer" />
      </div>
    </div>
  );
}
