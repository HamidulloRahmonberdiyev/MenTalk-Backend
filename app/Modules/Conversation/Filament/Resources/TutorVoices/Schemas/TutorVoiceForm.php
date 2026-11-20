<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices\Schemas;

use App\Modules\Speech\Support\GeminiVoices;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class TutorVoiceForm
{
    private const LOCALES = ['uz' => "O'zbekcha", 'ru' => 'Русский', 'en' => 'English'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ovoz')
                ->columns(2)
                ->schema([
                    Select::make('voice')
                        ->label('Gemini ovozi')
                        ->helperText('Jinsi ovozdan aniqlanadi. Erkak ovozda tutor tilning "Tutor ismi (erkak ovoz)" ismi bilan gapiradi.')
                        ->options(GeminiVoices::options())
                        ->searchable()
                        ->required(),
                    TextInput::make('sort')
                        ->label('Tartib raqami')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('active')
                        ->label('Ilovada ko\'rinsin')
                        ->helperText('O\'chirilsa, ilova uni ro\'yxatda ko\'rsatmaydi; uni tanlaganlar standart ovozga o\'tadi. Boshlangan suhbatlar o\'z ovozida davom etadi.')
                        ->default(true),
                ]),

            Section::make('Ilovada ko\'rinadigan nom')
                ->description('Qisqa sifat: "Iliq", "Yumshoq", "Aniq"... Ayol/erkak guruhini ilova o\'zi ko\'rsatadi.')
                ->schema([
                    Tabs::make('locales')->tabs(
                        collect(self::LOCALES)->map(fn (string $name, string $locale) => Tab::make($name)->schema([
                            TextInput::make("name.{$locale}")->label('Nomi')->required()->maxLength(30),
                        ]))->values()->all(),
                    ),
                ]),
        ]);
    }
}
