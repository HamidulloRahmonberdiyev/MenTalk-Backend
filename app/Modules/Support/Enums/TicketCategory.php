<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

enum TicketCategory: string
{
    case Bug = 'bug';
    case Idea = 'idea';
    case Question = 'question';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Xatolik',
            self::Idea => 'Taklif',
            self::Question => 'Savol',
            self::Other => 'Boshqa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bug => 'danger',
            self::Idea => 'success',
            self::Question => 'info',
            self::Other => 'gray',
        };
    }
}
