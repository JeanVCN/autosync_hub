<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Published = 'published';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case RequiresAction = 'requires_action';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
