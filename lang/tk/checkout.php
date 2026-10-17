<?php

return [
    'validation' => [
        'phone' => [
            'required' => 'Telefon belgiňizi giriziň',
            'missing_plus' => 'Belgi + bilen başlamaly',
            'strict_format' => 'Görnüşi: :example (:country)',
            'e164_format' => 'Belgini halkara görnüşinde giriziň, mysal üçin +99312345678',
            'e164_helper' => 'Halkara görnüşi: +<ýurt kody><belgi>',
            'strict_helper' => ':country üçin görnüş',
            'not_allowed' => 'Bu ýurduň belgileri goldanylmaýar',
            'config_error' => 'Sazlama ýalňyşlygy. Goldaw gullugyna ýüz tutuň.',
        ],
        'name' => [
            'required' => 'Adyňyzy giriziň',
        ],
        'comment' => [
            'too_long' => 'Teswir gaty uzyn (iň köp :max nyşan)',
        ],
    ],
    'summary' => [
        'contacts' => 'Habarlaşmak üçin',
        'name_label' => 'Adyňyz näme?',
        'phone_label' => 'Telefon belgisi',
        'phone_placeholder' => '+99312345678',
        'comment_label' => 'Sargyda teswir',
        'comment_placeholder' => 'Mysal üçin: jaň etmäň, çaga ýatyr',
    ],
];
