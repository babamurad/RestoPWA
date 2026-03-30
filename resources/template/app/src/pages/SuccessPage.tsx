import { CheckCircle, Home, ShoppingBag } from 'lucide-react';

interface SuccessPageProps {
  onGoHome: () => void;
  onViewOrders: () => void;
}

export function SuccessPage({ onGoHome, onViewOrders }: SuccessPageProps) {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6">
      <div className="text-center">
        {/* Success Animation */}
        <div className="relative mb-6">
          <div className="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center animate-scale-in">
            <CheckCircle size={48} className="text-green-500" />
          </div>
          <div className="absolute inset-0 w-24 h-24 mx-auto bg-green-500/20 rounded-full animate-ping" style={{ animationDuration: '2s' }} />
        </div>

        <h1 className="text-2xl font-bold text-gray-900 mb-2 animate-slide-up" style={{ animationDelay: '0.1s' }}>
          Заказ оформлен!
        </h1>
        <p className="text-gray-500 mb-8 animate-slide-up" style={{ animationDelay: '0.2s' }}>
          Ваш заказ успешно принят и скоро будет передан в работу
        </p>

        {/* Order Info Card */}
        <div className="bg-white rounded-2xl p-6 border border-gray-100 mb-8 animate-slide-up" style={{ animationDelay: '0.3s' }}>
          <div className="flex items-center justify-center gap-2 mb-4">
            <div className="w-3 h-3 bg-green-500 rounded-full animate-pulse" />
            <span className="text-sm font-medium text-green-600">Заказ принят</span>
          </div>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Номер заказа</span>
              <span className="font-mono font-medium text-gray-900">#ORD-004</span>
            </div>
            <div className="flex justify-between text-gray-600">
              <span>Примерное время</span>
              <span className="font-medium text-gray-900">30-40 мин</span>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="space-y-3 animate-slide-up" style={{ animationDelay: '0.4s' }}>
          <button
            onClick={onViewOrders}
            className="w-full flex items-center justify-center gap-2 px-6 py-4 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors touch-feedback"
          >
            <ShoppingBag size={20} />
            <span>Мои заказы</span>
          </button>
          <button
            onClick={onGoHome}
            className="w-full flex items-center justify-center gap-2 px-6 py-4 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors touch-feedback"
          >
            <Home size={20} />
            <span>На главную</span>
          </button>
        </div>
      </div>
    </div>
  );
}
