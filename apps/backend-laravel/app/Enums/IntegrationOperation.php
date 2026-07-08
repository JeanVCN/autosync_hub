<?php

namespace App\Enums;

enum IntegrationOperation: string
{
    case Publish = 'publish';
    case Update = 'update';
    case Delete = 'delete';
    case StatusCheck = 'status_check';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
