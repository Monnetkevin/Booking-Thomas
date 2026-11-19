<?php

namespace App\Enums;

/** Urgente : demande une action rapide, part aussi par e-mail. Info : à lire dans le back-office. */
enum NotificationLevel: string
{
    case Urgent = 'urgent';
    case Info = 'info';
}
