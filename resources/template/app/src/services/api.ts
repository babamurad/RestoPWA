const API_BASE = '/api/v1';

interface ApiResponse<T> {
  data: T;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface MenuResponse {
  categories: CategoryApi[];
  products: ApiResponse<ProductApi[]>;
}

interface CategoryApi {
  id: number;
  name: string;
  sort_order: number;
  children: CategoryApi[];
}

interface ProductApi {
  id: string;
  name: string;
  price: number;
  description?: string;
  image_url?: string;
  is_available: boolean;
  weight_g?: number;
  category?: {
    id: number;
    name: string;
  };
}

interface RestaurantApi {
  id: string;
  slug?: string;
  name: string;
  image?: string;
  cover_image?: string;
  rating?: number;
  review_count?: number;
  delivery_time?: string;
  delivery_fee: number;
  min_order?: number;
  is_active?: boolean;
  categories?: string[];
  menu?: ProductApi[];
  description?: string;
}

function getVendorId(): string | null {
  if (typeof window !== 'undefined') {
    const pathParts = window.location.hostname.split('.');
    if (pathParts.length > 0) {
      return pathParts[0];
    }
  }
  return localStorage.getItem('vendor_id');
}

async function fetchApi<T>(endpoint: string, options?: RequestInit): Promise<T> {
  const vendorId = getVendorId();
  
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(vendorId ? { 'X-Vendor-ID': vendorId } : {}),
      ...options?.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.status}`);
  }

  return response.json();
}

export async function fetchRestaurants(): Promise<RestaurantApi[]> {
  return fetchApi<RestaurantApi[]>('/restaurants');
}

export async function fetchRestaurant(slug: string): Promise<RestaurantApi> {
  return fetchApi<RestaurantApi>(`/restaurants/${slug}`);
}

export async function fetchMenu(vendorSlug: string, categoryId?: number) {
  const params = new URLSearchParams();
  if (categoryId) {
    params.set('category_id', categoryId.toString());
  }

  const endpoint = `/menu/${vendorSlug}${params.toString() ? `?${params}` : ''}`;
  return fetchApi<MenuResponse>(endpoint);
}

export async function fetchProduct(productId: string): Promise<ProductApi> {
  return fetchApi<ProductApi>(`/menu/product/${productId}`);
}

export async function fetchCategories(): Promise<{ data: CategoryApi[] }> {
  return fetchApi<{ data: CategoryApi[] }>('/categories');
}

export { API_BASE, getVendorId };
export type { ApiResponse, MenuResponse, CategoryApi, ProductApi, RestaurantApi };
