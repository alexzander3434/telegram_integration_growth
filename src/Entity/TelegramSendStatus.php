<?php

namespace App\Entity;

enum TelegramSendStatus: string
{
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
