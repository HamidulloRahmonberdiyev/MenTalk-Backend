<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts;

use App\Filament\Resources\AiPrompts\Pages\CreateAiPrompt;
use App\Filament\Resources\AiPrompts\Pages\EditAiPrompt;
use App\Filament\Resources\AiPrompts\Pages\ListAiPrompts;
use App\Filament\Resources\AiPrompts\Schemas\AiPromptForm;
use App\Filament\Resources\AiPrompts\Tables\AiPromptsTable;
use App\Models\AiPrompt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Prompts are looked up by key in code, so the shared keys are fixed by seeders: admins edit their
 * text, never delete them. Admins may add a language variant ("tutor.flow@en") when one language
 * needs different wording; only those can be deleted, which falls back to the shared prompt.
 */
final class AiPromptResource extends Resource
{
    protected static ?string $model = AiPrompt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $modelLabel = 'prompt';

    protected static ?string $pluralModelLabel = 'AI promptlar';

    protected static string|\UnitEnum|null $navigationGroup = 'Kontent';

    protected static ?int $navigationSort = 2;

    public static function canDelete($record): bool
    {
        return $record instanceof AiPrompt && $record->isVariant();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AiPromptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiPromptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiPrompts::route('/'),
            'create' => CreateAiPrompt::route('/create'),
            'edit' => EditAiPrompt::route('/{record}/edit'),
        ];
    }
}
