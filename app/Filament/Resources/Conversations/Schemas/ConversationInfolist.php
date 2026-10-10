<?php

declare(strict_types=1);

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Metadata and scores only: message text is private and is not shown to admins. */
final class ConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Suhbat')->columns(3)->schema([
                TextEntry::make('user.name')->label('Foydalanuvchi'),
                TextEntry::make('scenario.title.uz')->label('Stsenariy'),
                TextEntry::make('state')->label('Holat')->badge(),
                TextEntry::make('started_at')->label('Boshlangan')->dateTime('d.m.Y H:i'),
                TextEntry::make('ended_at')->label('Tugagan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('duration_sec')->label('Davomiyligi (soniya)')->numeric(),
                TextEntry::make('learner_turns')->label('Foydalanuvchi javoblari')->numeric(),
            ]),
            Section::make('Natija')->columns(4)->schema([
                TextEntry::make('result.overall')->label('Umumiy')->placeholder('—'),
                TextEntry::make('result.speech')->label('Nutq')->placeholder('—'),
                TextEntry::make('result.vocabulary')->label('Lug\'at')->placeholder('—'),
                TextEntry::make('result.grammar')->label('Grammatika')->placeholder('—'),
            ]),
        ]);
    }
}
