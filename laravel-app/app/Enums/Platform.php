<?php

namespace App\Enums;

enum Platform: string
{
    case Android = 'android';
    case Ios = 'ios';

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Google Play',
            self::Ios => 'App Store',
        };
    }
}
