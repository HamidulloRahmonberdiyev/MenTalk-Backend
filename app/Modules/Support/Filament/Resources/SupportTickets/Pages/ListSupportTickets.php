<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets\Pages;

use App\Modules\Support\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

final class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
