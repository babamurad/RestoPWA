export interface Category {
  id: string;
  name: string;
  icon?: string;
}

export interface FoodItem {
  id: string;
  name: string;
  description?: string;
  price: number;
  image?: string;
  image_url?: string;
  category?: string;
  category_id?: number;
  weight?: string;
  weight_g?: number;
  calories?: number;
  isPopular?: boolean;
  is_available?: boolean;
}

export interface Restaurant {
  id: string;
  slug?: string;
  name: string;
  description?: string;
  image?: string;
  coverImage?: string;
  rating?: number;
  reviewCount?: number;
  deliveryTime?: string;
  delivery_time?: string;
  deliveryFee: number;
  delivery_fee: number;
  minOrder?: number;
  min_order?: number;
  categories?: string[];
  menu?: FoodItem[];
  isOpen?: boolean;
  is_active?: boolean;
}

export interface MenuCategory {
  id: number;
  name: string;
  sort_order: number;
  children: MenuCategory[];
}

export interface CartItem {
  foodItem: FoodItem;
  quantity: number;
  restaurantId: string;
  restaurantName: string;
}

export interface Order {
  id: string;
  items: CartItem[];
  total: number;
  status: 'pending' | 'confirmed' | 'preparing' | 'delivering' | 'delivered' | 'cancelled';
  createdAt: string;
  deliveryAddress: string;
  paymentMethod: string;
  restaurantName: string;
}

export interface User {
  id: string;
  name: string;
  email: string;
  phone: string;
  avatar: string;
  addresses: Address[];
  paymentMethods: PaymentMethod[];
}

export interface Address {
  id: string;
  label: string;
  address: string;
  isDefault: boolean;
}

export interface PaymentMethod {
  id: string;
  type: 'card' | 'cash' | 'wallet';
  label: string;
  last4?: string;
  isDefault: boolean;
}

export type Page = 'home' | 'restaurant' | 'cart' | 'checkout' | 'profile' | 'orders';
