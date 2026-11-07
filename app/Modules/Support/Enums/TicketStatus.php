<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yangi',
            self::InProgress => 'Ko‘rib chiqilmoqda',
            self::Resolved => 'Hal qilindi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InProgress => 'info',
            self::Resolved => 'success',
        };
    }

    /** @return array<string, string> value => label, for selects and filters */
    public static function options(): array
    {
        return array_combine(array_column(self::cases(), 'value'), array_map(fn (self $case) => $case->label(), self::cases()));
    }
}
