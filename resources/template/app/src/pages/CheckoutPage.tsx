import { useState } from 'react';
import { MapPin, CreditCard, Wallet, Banknote, ChevronRight, Check, Truck } from 'lucide-react';
import { Header } from '@/components/Header';
import type { CartItem } from '@/types';
import { currentUser } from '@/data/mockData';

interface CheckoutPageProps {
  cartItems: CartItem[];
  onBack: () => void;
  onOrderComplete: () => void;
}

export function CheckoutPage({ cartItems, onBack, onOrderComplete }: CheckoutPageProps) {
  const [selectedAddress, setSelectedAddress] = useState(currentUser.addresses[0].id);
  const [selectedPayment, setSelectedPayment] = useState(currentUser.paymentMethods[0].id);
  const [comment, setComment] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const totalAmount = cartItems.reduce(
    (sum, item) => sum + item.foodItem.price * item.quantity,
    0
  );

  const deliveryFee = 0; // Free delivery for demo
  const finalTotal = totalAmount + deliveryFee;

  const handleSubmit = async () => {
    setIsSubmitting(true);
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setIsSubmitting(false);
    onOrderComplete();
  };

  const getPaymentIcon = (type: string) => {
    switch (type) {
      case 'card':
        return <CreditCard size={20} className="text-gray-600" />;
      case 'cash':
        return <Banknote size={20} className="text-gray-600" />;
      case 'wallet':
        return <Wallet size={20} className="text-gray-600" />;
      default:
        return <CreditCard size={20} className="text-gray-600" />;
    }
  };

  return (
    <div className="min-h-screen pb-32 bg-gray-50">
      <Header showBack onBack={onBack} title="Оформление заказа" />

      <main className="px-4 py-4 space-y-4">
        {/* Delivery Address */}
        <section className="bg-white rounded-2xl p-4 border border-gray-100">
          <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <MapPin size={20} className="text-orange-500" />
            Адрес доставки
          </h3>
          <div className="space-y-2">
            {currentUser.addresses.map((address) => (
              <button
                key={address.id}
                onClick={() => setSelectedAddress(address.id)}
                className={`w-full flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 ${
                  selectedAddress === address.id
                    ? 'border-orange-500 bg-orange-50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                <div
                  className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                    selectedAddress === address.id
                      ? 'border-orange-500 bg-orange-500'
                      : 'border-gray-300'
                  }`}
                >
                  {selectedAddress === address.id && <Check size={12} className="text-white" />}
                </div>
                <div className="flex-1 text-left">
                  <span className="text-sm font-medium text-gray-700">{address.label}</span>
                  <p className="text-sm text-gray-500">{address.address}</p>
                </div>
              </button>
            ))}
          </div>
          <button className="mt-3 w-full py-2 text-sm text-orange-500 font-medium hover:bg-orange-50 rounded-lg transition-colors">
            + Добавить новый адрес
          </button>
        </section>

        {/* Payment Method */}
        <section className="bg-white rounded-2xl p-4 border border-gray-100">
          <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <CreditCard size={20} className="text-orange-500" />
            Способ оплаты
          </h3>
          <div className="space-y-2">
            {currentUser.paymentMethods.map((method) => (
              <button
                key={method.id}
                onClick={() => setSelectedPayment(method.id)}
                className={`w-full flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 ${
                  selectedPayment === method.id
                    ? 'border-orange-500 bg-orange-50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                <div
                  className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                    selectedPayment === method.id
                      ? 'border-orange-500 bg-orange-500'
                      : 'border-gray-300'
                  }`}
                >
                  {selectedPayment === method.id && <Check size={12} className="text-white" />}
                </div>
                {getPaymentIcon(method.type)}
                <div className="flex-1 text-left">
                  <span className="text-sm font-medium text-gray-700">{method.label}</span>
                  {method.last4 && (
                    <span className="text-sm text-gray-500"> •••• {method.last4}</span>
                  )}
                </div>
              </button>
            ))}
          </div>
        </section>

        {/* Comment */}
        <section className="bg-white rounded-2xl p-4 border border-gray-100">
          <h3 className="font-semibold text-gray-900 mb-3">Комментарий к заказу</h3>
          <textarea
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            placeholder="Например: не звоните в домофон"
            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
            rows={3}
          />
        </section>

        {/* Order Summary */}
        <section className="bg-white rounded-2xl p-4 border border-gray-100">
          <h3 className="font-semibold text-gray-900 mb-3">Сводка заказа</h3>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Товары ({cartItems.reduce((sum, item) => sum + item.quantity, 0)})</span>
              <span>{totalAmount} ₽</span>
            </div>
            <div className="flex justify-between text-gray-600">
              <span className="flex items-center gap-1">
                <Truck size={14} />
                Доставка
              </span>
              <span className="text-green-600">Бесплатно</span>
            </div>
            <div className="pt-2 border-t border-gray-100 flex justify-between">
              <span className="font-semibold text-gray-900">Итого</span>
              <span className="font-bold text-xl text-gray-900">{finalTotal} ₽</span>
            </div>
          </div>
        </section>
      </main>

      {/* Submit Button */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 safe-bottom">
        <div className="max-w-lg mx-auto">
          <button
            onClick={handleSubmit}
            disabled={isSubmitting}
            className="w-full flex items-center justify-center gap-2 px-6 py-4 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors touch-feedback btn-press disabled:opacity-70 disabled:cursor-not-allowed"
          >
            {isSubmitting ? (
              <>
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span>Оформление...</span>
              </>
            ) : (
              <>
                <span>Подтвердить заказ</span>
                <ChevronRight size={20} />
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
