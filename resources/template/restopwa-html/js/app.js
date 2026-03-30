// RestoPWA - App Functions

// Icon SVGs
const icons = {
    pizza: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14-1 1"/><path d="m13.75 18.25-1.25 1.42"/><path d="m17.75 14.25-1.25 1.42"/><path d="M2 21a5 5 0 0 1 5-5h10a5 5 0 0 1 5 5v3H2v-3Z"/><path d="M12 2a8 8 0 0 0-8 8v5h16v-5a8 8 0 0 0-8-8Z"/></svg>',
    burger: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z"/><path d="M4 11h16"/><path d="M4 15h16"/></svg>',
    sushi: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8v10h16V10a8 8 0 0 0-8-8Z"/><path d="M8 10v10"/><path d="M12 10v10"/><path d="M16 10v10"/></svg>',
    pasta: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7"/><path d="M3 7h18"/><path d="M6 7v10"/><path d="M10 7v10"/><path d="M14 7v10"/><path d="M18 7v10"/></svg>',
    salad: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2Z"/><path d="M7 11V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4"/><path d="M12 3v4"/></svg>',
    dessert: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8v10h16V10a8 8 0 0 0-8-8Z"/><path d="M8 10v10"/><path d="M12 10v10"/><path d="M16 10v10"/></svg>',
    drink: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" x2="6.01" y1="2" y2="2"/><line x1="10" x2="10.01" y1="2" y2="2"/><line x1="14" x2="14.01" y1="2" y2="2"/></svg>',
    asian: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8v10h16V10a8 8 0 0 0-8-8Z"/><path d="M8 10v10"/><path d="M12 10v10"/><path d="M16 10v10"/></svg>',
};

let activeCategory = null;

// Render categories
function renderCategories() {
    const container = document.getElementById('categories');
    if (!container) return;
    
    container.innerHTML = categories.map(cat => `
        <button onclick="selectCategory('${cat.name}')" 
            class="flex items-center gap-2 px-4 py-2.5 rounded-full whitespace-nowrap transition-all duration-200 touch-feedback ${
                activeCategory === cat.name 
                    ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25' 
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }">
            ${icons[cat.icon] || icons.pizza}
            <span class="text-sm font-medium">${cat.name}</span>
        </button>
    `).join('');
}

// Select category
function selectCategory(name) {
    activeCategory = activeCategory === name ? null : name;
    renderCategories();
    renderNearbyRestaurants();
}

// Render popular restaurants (horizontal)
function renderPopularRestaurants() {
    const container = document.getElementById('popular-restaurants');
    if (!container) return;
    
    const popular = [...restaurants].sort((a, b) => b.rating - a.rating).slice(0, 5);
    
    container.innerHTML = popular.map(r => `
        <a href="restaurant.html?id=${r.id}" class="flex-shrink-0 w-72 bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback">
            <div class="relative h-36 overflow-hidden">
                <img src="${r.image}" alt="${r.name}" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" loading="lazy">
                ${r.deliveryFee === 0 ? '<div class="absolute top-3 left-3 px-2.5 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">Бесплатная доставка</div>' : ''}
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 truncate">${r.name}</h3>
                <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span class="font-medium text-gray-700">${r.rating}</span>
                        <span>(${r.reviewCount})</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>${r.deliveryTime}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 mt-2">
                    ${r.categories.slice(0, 3).map(cat => `<span class="text-xs text-gray-500">${cat}</span>`).join(' • ')}
                </div>
            </div>
        </a>
    `).join('');
}

// Render nearby restaurants (vertical)
function renderNearbyRestaurants() {
    const container = document.getElementById('nearby-restaurants');
    if (!container) return;
    
    let filtered = restaurants;
    if (activeCategory) {
        filtered = restaurants.filter(r => r.categories.includes(activeCategory));
    }
    
    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-500">Ничего не найдено</p>
                <button onclick="selectCategory(null)" class="mt-2 text-orange-500 font-medium">Сбросить фильтры</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = filtered.map(r => `
        <a href="restaurant.html?id=${r.id}" class="flex gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback">
            <div class="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl">
                <img src="${r.image}" alt="${r.name}" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="flex flex-col justify-center flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 truncate">${r.name}</h3>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span class="font-medium text-gray-700">${r.rating}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>${r.deliveryTime}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1 mt-1 text-sm text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="truncate">${r.categories.join(' • ')}</span>
                </div>
                ${r.deliveryFee === 0 
                    ? '<span class="mt-2 text-xs font-medium text-green-600">Бесплатная доставка</span>' 
                    : `<span class="mt-2 text-xs text-gray-500">Доставка ${r.deliveryFee} ₽</span>`
                }
            </div>
        </a>
    `).join('');
}

// Filter restaurants by search query
function filterRestaurants(query) {
    const container = document.getElementById('nearby-restaurants');
    if (!container) return;
    
    const filtered = restaurants.filter(r => 
        r.name.toLowerCase().includes(query.toLowerCase()) ||
        r.categories.some(cat => cat.toLowerCase().includes(query.toLowerCase()))
    );
    
    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-500">Ничего не найдено по запросу "${query}"</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = filtered.map(r => `
        <a href="restaurant.html?id=${r.id}" class="flex gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback">
            <div class="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl">
                <img src="${r.image}" alt="${r.name}" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="flex flex-col justify-center flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 truncate">${r.name}</h3>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span class="font-medium text-gray-700">${r.rating}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>${r.deliveryTime}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1 mt-1 text-sm text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="truncate">${r.categories.join(' • ')}</span>
                </div>
            </div>
        </a>
    `).join('');
}

// Get URL parameter
function getUrlParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

// Status config for orders
const statusConfig = {
    pending: { label: 'Ожидает подтверждения', color: 'text-yellow-600 bg-yellow-50' },
    confirmed: { label: 'Подтверждён', color: 'text-blue-600 bg-blue-50' },
    preparing: { label: 'Готовится', color: 'text-orange-600 bg-orange-50' },
    delivering: { label: 'В пути', color: 'text-purple-600 bg-purple-50' },
    delivered: { label: 'Доставлен', color: 'text-green-600 bg-green-50' },
    cancelled: { label: 'Отменён', color: 'text-red-600 bg-red-50' },
};
