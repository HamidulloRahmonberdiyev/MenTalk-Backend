<?php

declare(strict_types=1);

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\InterfaceLanguage;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asosiy')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Kod')
                        ->helperText('ISO 639-1, masalan: ru, en, de. Yaratilgandan keyin o\'zgarmaydi: ilova, so\'zlar va suhbatlar shunga tayanadi.')
                        ->required()
                        ->regex('/^[a-z]{2,8}$/')
                        ->maxLength(8)
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    TextInput::make('name')
                        ->label('Inglizcha nomi')
                        ->helperText('AI promptlarida ishlatiladi: German, Turkish...')
                        ->required()
                        ->maxLength(40),
                    TextInput::make('tutor_name')
                        ->label('Tutor ismi')
                        ->required()
                        ->maxLength(40),
                    TextInput::make('voice')
                        ->label('Ovoz (TTS)')
                        ->helperText('Bo\'sh qolsa, standart ovoz ishlatiladi.')
                        ->maxLength(40),
                    TextInput::make('sort')
                        ->label('Tartib raqami')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('active')
                        ->label('Ilovada tanlash mumkin')
                        ->helperText('O\'chirilsa, yangi suhbat boshlanmaydi; saqlangan so\'zlar va tarix qoladi.')
                        ->default(false),
                ]),

            Section::make('Ilovada ko\'rinadigan nomi')
                ->schema([
                    Tabs::make('titles')->tabs(
                        collect(InterfaceLanguage::cases())
                            ->map(fn (InterfaceLanguage $locale) => Tab::make($locale->englishName())->schema([
                                TextInput::make("title.{$locale->value}")->label('Nomi')->required()->maxLength(40),
                            ]))
                            ->all(),
                    ),
                ]),

            Section::make('AI uchun')
                ->description('Promptlardagi {language}, {tutor} kabi o\'rin belgilariga qo\'yiladi. Ingliz tilida yozing.')
                ->columns(2)
                ->schema([
                    TextInput::make('script_note')
                        ->label('Yozuv izohi {script}')
                        ->helperText('Masalan: " (Cyrillic)". Lotin yozuvli tillar uchun bo\'sh.')
                        ->maxLength(40),
                    TextInput::make('basic_words')
                        ->label('Juda oddiy so\'zlar {basic_words}')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('dictionary_form')
                        ->label('Lug\'at shakli {dictionary_form}')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('follow_ups')
                        ->label('Davom ettiruvchi savollar {follow_ups}')
                        ->required()
                        ->maxLength(200),
                    Textarea::make('rules')
                        ->label('Tilga xos qoidalar {language_rules}')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
