<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Schemas;

use App\Models\AiPrompt;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AiPromptForm
{
    /** Placeholders the code substitutes; removing one silently breaks the tutor. */
    public const REQUIRED_PLACEHOLDERS = [
        'tutor.system' => ['{scenario}', '{topics}', '{learner}', '{level}', '{gender}', '{ui_language}', '{flow_rules}'],
        'lookup' => ['{gloss}'],
        'translate' => ['{language}'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('key')
                    ->label('Kalit')
                    ->helperText('Kod shu kalit bo\'yicha promptni topadi, shuning uchun o\'zgartirib bo\'lmaydi.')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('body')
                    ->label('Matn')
                    ->required()
                    ->rows(16)
                    ->helperText(fn (?AiPrompt $record) => self::placeholderHint($record))
                    ->rules([fn (?AiPrompt $record): Closure => self::mustKeepPlaceholders($record)]),
            ]),
        ]);
    }

    private static function placeholderHint(?AiPrompt $record): ?string
    {
        $placeholders = self::REQUIRED_PLACEHOLDERS[$record?->key] ?? null;

        return $placeholders ? 'Majburiy o\'rin belgilari: '.implode(' ', $placeholders) : null;
    }

    private static function mustKeepPlaceholders(?AiPrompt $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $missing = array_filter(
                self::REQUIRED_PLACEHOLDERS[$record?->key] ?? [],
                fn (string $placeholder) => ! str_contains((string) $value, $placeholder),
            );

            if ($missing !== []) {
                $fail('Quyidagi o\'rin belgilari yo\'q: '.implode(' ', $missing));
            }
        };
    }
}
