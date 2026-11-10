<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Housekeeping = 'housekeeping';
}
