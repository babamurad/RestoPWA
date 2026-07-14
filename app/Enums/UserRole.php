<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case RESTAURATEUR = 'restaurateur';
    case CLIENT = 'client';
    case COURIER = 'courier';
}
