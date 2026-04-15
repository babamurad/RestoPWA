<?php

/*
 * For more details about the configuration, see:
 * https://sweetalert2.github.io/#configuration
 */

use Jantinnerezo\LivewireAlert\Enums\Position;

return [
    'position'          => Position::Center,
    'timer'             => 3000,
    'toast'             => false,
    'text'              => null,
    'confirmButtonText' => 'Да',
    'cancelButtonText'  => 'Отмена',
    'denyButtonText'    => 'Нет',
    'showCancelButton'  => false,
    'showConfirmButton' => false,
    'backdrop'          => true,
];
