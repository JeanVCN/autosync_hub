<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case Olx = 'olx';
    case MercadoLivre = 'mercado_livre';
    case Icarros = 'icarros';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
