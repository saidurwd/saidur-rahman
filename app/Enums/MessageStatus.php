<?php

namespace App\Enums;

enum MessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Spam = 'spam';
}
