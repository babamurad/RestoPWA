// App shell: offline banner, page titles, global alerts
export default {
    offline: {
        title: 'Режим оффлайн',
        text: 'Отсутствует интернет-подключение. Вы можете продолжать просмотр, заказы будут синхронизированы при появлении сети.',
    },
    titles: {
        home: 'Каталог ресторанов',
        restaurant_menu: 'Меню ресторана',
        cart: 'Корзина',
        checkout: 'Оформление заказа',
        login: 'Вход',
        register: 'Регистрация',
        profile: 'Профиль',
        addresses: 'Мои адреса',
        payments: 'Способы оплаты',
        support: 'Служба поддержки',
        orders: 'Заказы',
    },
    auth_required: {
        title: 'Требуется авторизация',
        text: 'У вас есть сохраненный заказ, но время сессии истекло. Войдите, чтобы отправить его.',
    },
};
