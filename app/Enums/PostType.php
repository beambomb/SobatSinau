<?php

namespace App\Enums;

enum PostType: string
{
    case Announcement = 'announcement';
    case Material = 'material';
    case Discussion = 'discussion';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
