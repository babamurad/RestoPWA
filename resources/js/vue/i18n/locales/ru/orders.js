// Order history page and orders store
export default {
    title: 'Мои заказы',
    total_count: 'Всего: {n}',
    empty: 'У вас пока нет оформленных заказов',
    go_shopping: 'Перейти к покупкам',
    order_number: 'Заказ #{id}',
    sum: 'Сумма:',
    your_rating: 'Ваша оценка {rating} из 5',
    rate: 'Оценить',
    repeat: 'Повторить',
    review: {
        title: 'Оцените заказ',
        rating: 'Оценка',
        star_label: '{n} из 5',
        placeholder: 'Что понравилось или что можно улучшить? (необязательно)',
        sending: 'Отправка...',
        send: 'Отправить',
        already_reviewed: 'Вы уже оценили этот заказ.',
        send_error: 'Не удалось отправить отзыв. Попробуйте ещё раз.',
    },
    repeat_dialog: {
        title: 'Повторить заказ?',
        text: 'Текущая корзина будет очищена, и все блюда из этого заказа будут добавлены.',
        confirm: 'Повторить',
        fallback_confirm: 'Добавить все блюда из этого заказа в корзину?',
    },
    errors: {
        submit: 'Ошибка оформления заказа',
        load_list: 'Ошибка загрузки заказов',
        load_one: 'Ошибка загрузки заказа',
    },
};
