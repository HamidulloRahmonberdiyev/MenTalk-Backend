<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Schemas;

use App\Models\AiPrompt;
use App\Models\Language;
use App\Services\Ai\PromptStore;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class AiPromptForm
{
    /** Placeholders the code substitutes; removing one silently breaks the tutor. */
    public const REQUIRED_PLACEHOLDERS = [
        'tutor.system' => ['{scenario}', '{topics}', '{learner}', '{level}', '{gender}', '{ui_language}', '{flow_rules}'],
        'evaluation' => ['{gloss}'],
        'lookup' => ['{gloss}'],
        'translate' => ['{language}'],
    ];

    /**
     * A shared prompt serves every taught language, so it must name the language through a
     * placeholder. A language variant may name it directly.
     */
    public const SHARED_PLACEHOLDERS = [
        'tutor.system' => ['{language}', '{tutor}'],
        'turn.opening' => ['{language}'],
        'turn.audio' => ['{language}'],
        'hint' => ['{language}'],
        'evaluation' => ['{language}'],
        'lookup' => ['{language}'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('key')
                    ->label('Kalit')
                    ->helperText('Kod shu kalit bo\'yicha promptni topadi, shuning uchun o\'zgartirib bo\'lmaydi.')
                    ->disabled()
                    ->dehydrated(false)
                    ->hiddenOn('create')
                    ->columnSpanFull(),
                Select::make('base_key')
                    ->label('Qaysi prompt')
                    ->options(fn () => self::sharedKeys())
                    ->required()
                    ->live()
                    ->visibleOn('create'),
                Select::make('language')
                    ->label('Qaysi til uchun')
                    ->helperText('Shu til uchun umumiy prompt o\'rniga ishlatiladi.')
                    ->options(fn () => Language::query()->orderBy('sort')->pluck('name', 'code')->all())
                    ->required()
                    ->rules([fn (Get $get): Closure => self::variantMustBeNew($get('base_key'))])
                    ->visibleOn('create'),
                Textarea::make('body')
                    ->label('Matn')
                    ->required()
                    ->rows(16)
                    ->columnSpanFull()
                    ->helperText(fn (?AiPrompt $record, Get $get) => self::placeholderHint(self::keyOf($record, $get)))
                    ->rules([fn (?AiPrompt $record, Get $get): Closure => self::mustKeepPlaceholders(self::keyOf($record, $get))]),
            ]),
        ]);
    }

    /** @return list<string> */
    public static function requiredPlaceholders(string $key): array
    {
        $base = PromptStore::baseKey($key);
        $shared = $base === $key ? self::SHARED_PLACEHOLDERS[$base] ?? [] : [];

        return [...self::REQUIRED_PLACEHOLDERS[$base] ?? [], ...$shared];
    }

    private static function keyOf(?AiPrompt $record, Get $get): ?string
    {
        if ($record !== null) {
            return $record->key;
        }

        return $get('base_key') && $get('language') ? $get('base_key').PromptStore::VARIANT_SEPARATOR.$get('language') : null;
    }

    /** @return array<string, string> */
    private static function sharedKeys(): array
    {
        return AiPrompt::query()->orderBy('key')->pluck('key')
            ->reject(fn (string $key) => $key !== PromptStore::baseKey($key))
            ->mapWithKeys(fn (string $key) => [$key => $key])
            ->all();
    }

    private static function placeholderHint(?string $key): ?string
    {
        $placeholders = $key === null ? [] : self::requiredPlaceholders($key);

        return $placeholders ? 'Majburiy o\'rin belgilari: '.implode(' ', $placeholders) : null;
    }

    private static function mustKeepPlaceholders(?string $key): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($key): void {
            $missing = array_filter(
                $key === null ? [] : self::requiredPlaceholders($key),
                fn (string $placeholder) => ! str_contains((string) $value, $placeholder),
            );

            if ($missing !== []) {
                $fail('Quyidagi o\'rin belgilari yo\'q: '.implode(' ', $missing));
            }
        };
    }

    private static function variantMustBeNew(?string $baseKey): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($baseKey): void {
            if ($baseKey !== null && AiPrompt::query()->where('key', $baseKey.PromptStore::VARIANT_SEPARATOR.$value)->exists()) {
                $fail('Bu til uchun variant allaqachon bor: uni tahrirlang.');
            }
        };
    }
}
