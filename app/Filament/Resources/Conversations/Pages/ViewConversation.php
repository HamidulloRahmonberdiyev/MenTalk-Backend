<?php

declare(strict_types=1);

namespace App\Filament\Resources\Conversations\Pages;

use App\Filament\Resources\Conversations\ConversationResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;
}
