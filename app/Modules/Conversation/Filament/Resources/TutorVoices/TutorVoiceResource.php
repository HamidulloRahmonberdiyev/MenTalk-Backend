<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices;

use App\Modules\Conversation\Filament\Resources\TutorVoices\Pages\CreateTutorVoice;
use App\Modules\Conversation\Filament\Resources\TutorVoices\Pages\EditTutorVoice;
use App\Modules\Conversation\Filament\Resources\TutorVoices\Pages\ListTutorVoices;
use App\Modules\Conversation\Filament\Resources\TutorVoices\Schemas\TutorVoiceForm;
use App\Modules\Conversation\Filament\Resources\TutorVoices\Tables\TutorVoicesTable;
use App\Modules\Conversation\Models\TutorVoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The voices learners pick from in the app (Settings → Tutor). Conversations keep the Gemini voice
 * itself, not the entry, so changing or deleting an entry never changes a conversation under way.
 */
final class TutorVoiceResource extends Resource
{
    protected static ?string $model = TutorVoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static ?string $modelLabel = 'tutor ovozi';

    protected static ?string $pluralModelLabel = 'Tutor ovozlari';

    protected static string|\UnitEnum|null $navigationGroup = 'Kontent';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TutorVoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorVoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutorVoices::route('/'),
            'create' => CreateTutorVoice::route('/create'),
            'edit' => EditTutorVoice::route('/{record}/edit'),
        ];
    }
}
