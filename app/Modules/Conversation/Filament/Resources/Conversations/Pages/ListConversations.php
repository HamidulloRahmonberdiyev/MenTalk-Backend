<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\Conversations\Pages;

use App\Modules\Conversation\Filament\Resources\Conversations\ConversationResource;
use Filament\Resources\Pages\ListRecords;

final class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;
}
