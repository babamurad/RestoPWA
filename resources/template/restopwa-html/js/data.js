// RestoPWA - Data
const categories = [
    { id: '1', name: 'Пицца', icon: 'pizza' },
    { id: '2', name: 'Бургеры', icon: 'burger' },
    { id: '3', name: 'Суши', icon: 'sushi' },
    { id: '4', name: 'Паста', icon: 'pasta' },
    { id: '5', name: 'Салаты', icon: 'salad' },
    { id: '6', name: 'Десерты', icon: 'dessert' },
    { id: '7', name: 'Напитки', icon: 'drink' },
    { id: '8', name: 'Азиатская', icon: 'asian' },
];

const restaurants = [
    {
        id: '1',
        name: 'Pepperoni Pizza',
        description: 'Аутентичная итальянская пицца из дровяной печи. Тонкое тесто, свежие ингредиенты.',
        image: 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&h=400&fit=crop',
        rating: 4.8,
        reviewCount: 1247,
        deliveryTime: '25-35 мин',
        deliveryFee: 0,
        minOrder: 800,
        categories: ['Пицца', 'Паста', 'Итальянская'],
        isOpen: true,
        menu: [
            { id: 'p1', name: 'Пепперони', description: 'Томатный соус, моцарелла, пепперони, орегано', price: 649, image: 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=200&h=200&fit=crop', category: 'Пицца', weight: '450 г', isPopular: true },
            { id: 'p2', name: 'Маргарита', description: 'Томатный соус, моцарелла, свежий базилик', price: 499, image: 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=200&h=200&fit=crop', category: 'Пицца', weight: '400 г' },
            { id: 'p3', name: 'Четыре сыра', description: 'Моцарелла, пармезан, горгонзола, чеддер', price: 749, image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=200&h=200&fit=crop', category: 'Пицца', weight: '480 г', isPopular: true },
            { id: 'p4', name: 'Карбонара', description: 'Спагетти, бекон, яйцо, пармезан, черный перец', price: 549, image: 'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=200&h=200&fit=crop', category: 'Паста', weight: '350 г' },
            { id: 'p5', name: 'Цезарь с курицей', description: 'Салат айсберг, курица, пармезан, гренки, соус цезарь', price: 449, image: 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?w=200&h=200&fit=crop', category: 'Салаты', weight: '280 г' },
        ]
    },
    {
        id: '2',
        name: 'Burger House',
        description: 'Сочные бургеры из мраморной говядины. Ручная работа, свежие булочки.',
        image: 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800&h=400&fit=crop',
        rating: 4.6,
        reviewCount: 892,
        deliveryTime: '30-40 мин',
        deliveryFee: 99,
        minOrder: 600,
        categories: ['Бургеры', 'Американская'],
        isOpen: true,
        menu: [
            { id: 'b1', name: 'Классический бургер', description: 'Говяжья котлета, чеддер, салат, помидор, лук, соус', price: 499, image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=200&fit=crop', category: 'Бургеры', weight: '320 г', isPopular: true },
            { id: 'b2', name: 'Двойной чизбургер', description: 'Две говяжьи котлеты, двойной чеддер, бекон, соус', price: 699, image: 'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=200&h=200&fit=crop', category: 'Бургеры', weight: '450 г', isPopular: true },
            { id: 'b3', name: 'Картофель фри', description: 'Хрустящий картофель фри с солью', price: 249, image: 'https://images.unsplash.com/photo-1630384060421-cb20d0e0649d?w=200&h=200&fit=crop', category: 'Закуски', weight: '200 г' },
            { id: 'b4', name: 'Куриные крылья', description: 'Острые куриные крылья в соусе баффало', price: 399, image: 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=200&h=200&fit=crop', category: 'Закуски', weight: '300 г' },
        ]
    },
    {
        id: '3',
        name: 'Sakura Sushi',
        description: 'Аутентичная японская кухня. Свежайшая рыба, мастера-сушисты.',
        image: 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1553621042-f6e147245754?w=800&h=400&fit=crop',
        rating: 4.9,
        reviewCount: 2156,
        deliveryTime: '40-50 мин',
        deliveryFee: 149,
        minOrder: 1000,
        categories: ['Суши', 'Роллы', 'Японская'],
        isOpen: true,
        menu: [
            { id: 's1', name: 'Филадельфия', description: 'Лосось, сливочный сыр, огурец, авокадо', price: 599, image: 'https://images.unsplash.com/photo-1559410545-0bdcd187e0a6?w=200&h=200&fit=crop', category: 'Роллы', weight: '280 г', isPopular: true },
            { id: 's2', name: 'Калифорния', description: 'Краб, авокадо, огурец, икра тобико', price: 549, image: 'https://images.unsplash.com/photo-1617196034796-73dfa7b1fd56?w=200&h=200&fit=crop', category: 'Роллы', weight: '260 г' },
            { id: 's3', name: 'Сет "Сакура"', description: '12 видов роллов, 48 штук', price: 2499, image: 'https://images.unsplash.com/photo-1553621042-f6e147245754?w=200&h=200&fit=crop', category: 'Сеты', weight: '1.2 кг', isPopular: true },
            { id: 's4', name: 'Мисо суп', description: 'Традиционный японский суп с тофу и водорослями', price: 199, image: 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=200&h=200&fit=crop', category: 'Супы', weight: '300 мл' },
        ]
    },
    {
        id: '4',
        name: 'Green Salad',
        description: 'Полезные и вкусные салаты. Свежие овощи, качественные ингредиенты.',
        image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=800&h=400&fit=crop',
        rating: 4.5,
        reviewCount: 567,
        deliveryTime: '20-30 мин',
        deliveryFee: 0,
        minOrder: 500,
        categories: ['Салаты', 'Здоровое питание'],
        isOpen: true,
        menu: [
            { id: 'g1', name: 'Греческий салат', description: 'Помидоры, огурцы, перец, оливки, фета, оливковое масло', price: 399, image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=200&h=200&fit=crop', category: 'Салаты', weight: '300 г' },
            { id: 'g2', name: 'Салат с авокадо', description: 'Авокадо, шпинат, киноа, гранат, лимонная заправка', price: 459, image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=200&h=200&fit=crop', category: 'Салаты', weight: '280 г', isPopular: true },
            { id: 'g3', name: 'Смузи боул', description: 'Асаи, банан, ягоды, гранола, кокосовые чипсы', price: 499, image: 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=200&h=200&fit=crop', category: 'Боулы', weight: '350 г' },
        ]
    },
    {
        id: '5',
        name: 'Sweet Dreams',
        description: 'Десерты ручной работы. Торты, пирожные, мороженое.',
        image: 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1558301211-0d8c8ddee6ec?w=800&h=400&fit=crop',
        rating: 4.7,
        reviewCount: 743,
        deliveryTime: '25-35 мин',
        deliveryFee: 99,
        minOrder: 400,
        categories: ['Десерты', 'Выпечка'],
        isOpen: true,
        menu: [
            { id: 'd1', name: 'Тирамису', description: 'Классический итальянский десерт с маскарпоне и кофе', price: 349, image: 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=200&h=200&fit=crop', category: 'Десерты', weight: '150 г', isPopular: true },
            { id: 'd2', name: 'Чизкейк Нью-Йорк', description: 'Классический чизкейк с ягодным соусом', price: 399, image: 'https://images.unsplash.com/photo-1524351199678-941a58a3df50?w=200&h=200&fit=crop', category: 'Десерты', weight: '180 г' },
            { id: 'd3', name: 'Макаруны (набор)', description: '6 макарун разных вкусов', price: 599, image: 'https://images.unsplash.com/photo-1569864358642-9d1684040f43?w=200&h=200&fit=crop', category: 'Десерты', weight: '120 г' },
        ]
    },
    {
        id: '6',
        name: 'Coffee Time',
        description: 'Специалитет кофе и свежая выпечка. Идеальное место для завтрака.',
        image: 'https://images.unsplash.com/photo-1554118811-0d8c8ddee6ec?w=400&h=300&fit=crop',
        coverImage: 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=400&fit=crop',
        rating: 4.4,
        reviewCount: 428,
        deliveryTime: '15-25 мин',
        deliveryFee: 0,
        minOrder: 300,
        categories: ['Напитки', 'Выпечка'],
        isOpen: true,
        menu: [
            { id: 'c1', name: 'Капучино', description: 'Эспрессо с молочной пенкой', price: 249, image: 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=200&h=200&fit=crop', category: 'Кофе', weight: '250 мл' },
            { id: 'c2', name: 'Латте', description: 'Эспрессо с молоком и нежной пенкой', price: 279, image: 'https://images.unsplash.com/photo-1570968992193-d6ea066f0e5c?w=200&h=200&fit=crop', category: 'Кофе', weight: '350 мл' },
            { id: 'c3', name: 'Круассан', description: 'Свежий слоёный круассан', price: 199, image: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=200&h=200&fit=crop', category: 'Выпечка', weight: '80 г' },
        ]
    },
];

const currentUser = {
    id: '1',
    name: 'Александр Петров',
    email: 'alex@example.com',
    phone: '+7 (999) 123-45-67',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200&h=200&fit=crop',
    addresses: [
        { id: '1', label: 'Дом', address: 'ул. Ленина, 15, кв. 42', isDefault: true },
        { id: '2', label: 'Работа', address: 'пр. Мира, 78, офис 305', isDefault: false },
    ],
    paymentMethods: [
        { id: '1', type: 'card', label: 'Visa', last4: '4242', isDefault: true },
        { id: '2', type: 'cash', label: 'Наличные', isDefault: false },
    ],
};

const orderHistory = [
    {
        id: 'ORD-001',
        items: [
            { foodItem: { id: 'p1', name: 'Пепперони', description: 'Томатный соус, моцарелла, пепперони', price: 649, image: '', category: 'Пицца' }, quantity: 1, restaurantId: '1', restaurantName: 'Pepperoni Pizza' },
        ],
        total: 649,
        status: 'delivered',
        createdAt: '2024-01-15T18:30:00',
        deliveryAddress: 'ул. Ленина, 15, кв. 42',
        paymentMethod: 'Картой онлайн',
        restaurantName: 'Pepperoni Pizza',
    },
    {
        id: 'ORD-002',
        items: [
            { foodItem: { id: 'b1', name: 'Классический бургер', description: 'Говяжья котлета, чеддер, салат', price: 499, image: '', category: 'Бургеры' }, quantity: 2, restaurantId: '2', restaurantName: 'Burger House' },
            { foodItem: { id: 'b3', name: 'Картофель фри', description: 'Хрустящий картофель фри', price: 249, image: '', category: 'Закуски' }, quantity: 1, restaurantId: '2', restaurantName: 'Burger House' },
        ],
        total: 1247,
        status: 'delivered',
        createdAt: '2024-01-10T19:15:00',
        deliveryAddress: 'ул. Ленина, 15, кв. 42',
        paymentMethod: 'Картой онлайн',
        restaurantName: 'Burger House',
    },
    {
        id: 'ORD-003',
        items: [
            { foodItem: { id: 's1', name: 'Филадельфия', description: 'Лосось, сливочный сыр, огурец', price: 599, image: '', category: 'Роллы' }, quantity: 2, restaurantId: '3', restaurantName: 'Sakura Sushi' },
        ],
        total: 1198,
        status: 'delivered',
        createdAt: '2024-01-05T20:00:00',
        deliveryAddress: 'пр. Мира, 78, офис 305',
        paymentMethod: 'Наличными',
        restaurantName: 'Sakura Sushi',
    },
];

// Cart functions
function getCart() {
    const cart = localStorage.getItem('restopwa_cart');
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem('restopwa_cart', JSON.stringify(cart));
}

function addToCart(foodItem, restaurant) {
    let cart = getCart();
    
    // Check if adding from different restaurant
    if (cart.length > 0 && cart[0].restaurantId !== restaurant.id) {
        if (confirm('В корзине есть товары из другого ресторана. Очистить корзину?')) {
            cart = [];
        } else {
            return;
        }
    }
    
    const existingItem = cart.find(item => item.foodItem.id === foodItem.id);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            foodItem,
            quantity: 1,
            restaurantId: restaurant.id,
            restaurantName: restaurant.name,
        });
    }
    
    saveCart(cart);
    updateCartBadge();
}

function removeFromCart(itemId) {
    let cart = getCart();
    const existingItem = cart.find(item => item.foodItem.id === itemId);
    
    if (existingItem && existingItem.quantity > 1) {
        existingItem.quantity -= 1;
    } else {
        cart = cart.filter(item => item.foodItem.id !== itemId);
    }
    
    saveCart(cart);
    updateCartBadge();
}

function increaseQuantity(itemId) {
    let cart = getCart();
    const item = cart.find(item => item.foodItem.id === itemId);
    if (item) {
        item.quantity += 1;
        saveCart(cart);
        updateCartBadge();
    }
}

function decreaseQuantity(itemId) {
    let cart = getCart();
    const item = cart.find(item => item.foodItem.id === itemId);
    
    if (item && item.quantity > 1) {
        item.quantity -= 1;
    } else {
        cart = cart.filter(item => item.foodItem.id !== itemId);
    }
    
    saveCart(cart);
    updateCartBadge();
}

function removeItem(itemId) {
    let cart = getCart();
    cart = cart.filter(item => item.foodItem.id !== itemId);
    saveCart(cart);
    updateCartBadge();
}

function clearCart() {
    localStorage.removeItem('restopwa_cart');
    updateCartBadge();
}

function getCartTotal() {
    const cart = getCart();
    return cart.reduce((sum, item) => sum + item.foodItem.price * item.quantity, 0);
}

function getCartItemCount() {
    const cart = getCart();
    return cart.reduce((sum, item) => sum + item.quantity, 0);
}

function updateCartBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        const count = getCartItemCount();
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

// Get restaurant by ID
function getRestaurantById(id) {
    return restaurants.find(r => r.id === id);
}

// Get item quantity in cart
function getItemQuantity(itemId) {
    const cart = getCart();
    const item = cart.find(item => item.foodItem.id === itemId);
    return item ? item.quantity : 0;
}
