import { useState, useCallback } from 'react';
import './App.css';
import { BottomNav } from '@/components/BottomNav';
import { HomePage } from '@/pages/HomePage';
import { RestaurantPage } from '@/pages/RestaurantPage';
import { CartPage } from '@/pages/CartPage';
import { CheckoutPage } from '@/pages/CheckoutPage';
import { ProfilePage } from '@/pages/ProfilePage';
import { OrdersPage } from '@/pages/OrdersPage';
import { SuccessPage } from '@/pages/SuccessPage';
import type { Page, Restaurant, FoodItem, CartItem } from '@/types';

function App() {
  const [currentPage, setCurrentPage] = useState<Page>('home');
  const [selectedRestaurant, setSelectedRestaurant] = useState<Restaurant | null>(null);
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [showSuccess, setShowSuccess] = useState(false);

  // Cart operations
  const addToCart = useCallback((foodItem: FoodItem, restaurant: Restaurant) => {
    setCartItems((prev) => {
      // Check if adding from different restaurant
      if (prev.length > 0 && prev[0].restaurantId !== restaurant.id) {
        if (confirm('В корзине есть товары из другого ресторана. Очистить корзину?')) {
          return [{
            foodItem,
            quantity: 1,
            restaurantId: restaurant.id,
            restaurantName: restaurant.name,
          }];
        }
        return prev;
      }

      const existingItem = prev.find((item) => item.foodItem.id === foodItem.id);
      if (existingItem) {
        return prev.map((item) =>
          item.foodItem.id === foodItem.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }
      return [
        ...prev,
        {
          foodItem,
          quantity: 1,
          restaurantId: restaurant.id,
          restaurantName: restaurant.name,
        },
      ];
    });
  }, []);

  const removeFromCart = useCallback((itemId: string) => {
    setCartItems((prev) => {
      const existingItem = prev.find((item) => item.foodItem.id === itemId);
      if (existingItem && existingItem.quantity > 1) {
        return prev.map((item) =>
          item.foodItem.id === itemId
            ? { ...item, quantity: item.quantity - 1 }
            : item
        );
      }
      return prev.filter((item) => item.foodItem.id !== itemId);
    });
  }, []);

  const increaseQuantity = useCallback((itemId: string) => {
    setCartItems((prev) =>
      prev.map((item) =>
        item.foodItem.id === itemId
          ? { ...item, quantity: item.quantity + 1 }
          : item
      )
    );
  }, []);

  const decreaseQuantity = useCallback((itemId: string) => {
    setCartItems((prev) => {
      const existingItem = prev.find((item) => item.foodItem.id === itemId);
      if (existingItem && existingItem.quantity > 1) {
        return prev.map((item) =>
          item.foodItem.id === itemId
            ? { ...item, quantity: item.quantity - 1 }
            : item
        );
      }
      return prev.filter((item) => item.foodItem.id !== itemId);
    });
  }, []);

  const removeItem = useCallback((itemId: string) => {
    setCartItems((prev) => prev.filter((item) => item.foodItem.id !== itemId));
  }, []);

  const clearCart = useCallback(() => {
    setCartItems([]);
  }, []);

  // Navigation handlers
  const handleRestaurantClick = useCallback((restaurant: Restaurant) => {
    setSelectedRestaurant(restaurant);
    setCurrentPage('restaurant');
  }, []);

  const handleBackToHome = useCallback(() => {
    setSelectedRestaurant(null);
    setCurrentPage('home');
  }, []);

  const handleCheckout = useCallback(() => {
    setCurrentPage('checkout');
  }, []);

  const handleOrderComplete = useCallback(() => {
    clearCart();
    setShowSuccess(true);
  }, [clearCart]);

  const handleSuccessGoHome = useCallback(() => {
    setShowSuccess(false);
    setCurrentPage('home');
  }, []);

  const handleSuccessViewOrders = useCallback(() => {
    setShowSuccess(false);
    setCurrentPage('orders');
  }, []);

  const handleViewAllOrders = useCallback(() => {
    setCurrentPage('orders');
  }, []);

  // Calculate total cart items
  const cartItemCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);

  // Render current page
  const renderPage = () => {
    if (showSuccess) {
      return (
        <SuccessPage
          onGoHome={handleSuccessGoHome}
          onViewOrders={handleSuccessViewOrders}
        />
      );
    }

    switch (currentPage) {
      case 'home':
        return <HomePage onRestaurantClick={handleRestaurantClick} />;

      case 'restaurant':
        if (selectedRestaurant) {
          return (
            <RestaurantPage
              restaurant={selectedRestaurant}
              cartItems={cartItems}
              onBack={handleBackToHome}
              onAddToCart={addToCart}
              onRemoveFromCart={removeFromCart}
            />
          );
        }
        return <HomePage onRestaurantClick={handleRestaurantClick} />;

      case 'cart':
        return (
          <CartPage
            cartItems={cartItems}
            onBack={() => setCurrentPage('home')}
            onCheckout={handleCheckout}
            onIncreaseQuantity={increaseQuantity}
            onDecreaseQuantity={decreaseQuantity}
            onRemoveItem={removeItem}
          />
        );

      case 'checkout':
        return (
          <CheckoutPage
            cartItems={cartItems}
            onBack={() => setCurrentPage('cart')}
            onOrderComplete={handleOrderComplete}
          />
        );

      case 'profile':
        return <ProfilePage onViewAllOrders={handleViewAllOrders} />;

      case 'orders':
        return <OrdersPage onBack={() => setCurrentPage('profile')} />;

      default:
        return <HomePage onRestaurantClick={handleRestaurantClick} />;
    }
  };

  // Don't show bottom nav on certain pages
  const hideBottomNav = ['checkout', 'restaurant'].includes(currentPage) || showSuccess;

  return (
    <div className="min-h-screen bg-gray-50">
      <main className="max-w-lg mx-auto bg-white min-h-screen shadow-xl">
        {renderPage()}
        
        {!hideBottomNav && (
          <BottomNav
            currentPage={currentPage}
            onPageChange={setCurrentPage}
            cartItemCount={cartItemCount}
          />
        )}
      </main>
    </div>
  );
}

export default App;
