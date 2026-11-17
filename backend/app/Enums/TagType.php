<?php

namespace App\Enums;

enum TagType: string
{
    case Occasion = 'occasion';
    case Attribute = 'attribute';

    public function label(): string
    {
        return match ($this) {
            self::Occasion => 'مناسبة',
            self::Attribute => 'صفة',
        };
    }
}
