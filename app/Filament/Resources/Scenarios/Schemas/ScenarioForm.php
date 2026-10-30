<?php

declare(strict_types=1);

namespace App\Filament\Resources\Scenarios\Schemas;

use App\Models\Language;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class ScenarioForm
{
    private const LOCALES = ['uz' => "O'zbekcha", 'ru' => 'Русский', 'en' => 'English'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asosiy')
                ->columns(2)
                ->schema([
                    TextInput::make('id')
                        ->label('Identifikator')
                        ->helperText('Lotin harflar, raqam va chiziqcha. Yaratilgandan keyin o\'zgarmaydi: ilova va tarix shunga tayanadi.')
                        ->required()
                        ->alphaDash()
                        ->maxLength(24)
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    TextInput::make('icon')
                        ->label('Ikonka nomi')
                        ->required()
                        ->maxLength(40),
                    ColorPicker::make('tint')
                        ->label('Asosiy rang')
                        ->required(),
                    TextInput::make('sort')
                        ->label('Tartib raqami')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Repeater::make('gradient')
                        ->label('Gradient ranglari')
                        ->simple(ColorPicker::make('color'))
                        ->minItems(2)
                        ->maxItems(3)
                        ->defaultItems(2)
                        ->required(),
                    FileUpload::make('image_path')
                        ->label('Rasm')
                        ->image()
                        ->disk('public')
                        ->directory('scenarios')
                        ->maxSize(2048),
                    Toggle::make('active')
                        ->label('Ilovada ko\'rinsin')
                        ->helperText('O\'chirilsa, ilova keyingi ochilishda stsenariyni ko\'rmaydi. Tarix saqlanadi.')
                        ->default(true),
                ]),

            Section::make('Ilovada ko\'rinadigan matnlar')
                ->schema([
                    Tabs::make('locales')->tabs(
                        collect(self::LOCALES)->map(fn (string $name, string $locale) => Tab::make($name)->schema([
                            TextInput::make("title.{$locale}")->label('Sarlavha')->required()->maxLength(60),
                            TextInput::make("subtitle.{$locale}")->label('Qisqa tavsif')->required()->maxLength(120),
                        ]))->values()->all(),
                    ),
                ]),

            Section::make('AI uchun')
                ->description('Har bir til uchun alohida: stsenariy faqat shu yerda qo\'shilgan tillarda ochiladi. Foydalanuvchiga ko\'rsatilmaydi.')
                ->schema([
                    Repeater::make('briefs')
                        ->label('Tillar')
                        ->relationship()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['language'] ?? null)
                        ->schema([
                            Select::make('language')
                                ->label('Til')
                                ->options(fn () => Language::query()->orderBy('sort')->pluck('name', 'code')->all())
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            Textarea::make('brief')
                                ->label('Rol va vaziyat (ingliz tilida)')
                                ->helperText('{tutor} o\'rniga tilning tutori ismi qo\'yiladi.')
                                ->required()
                                ->rows(4),
                            Textarea::make('next_topics')
                                ->label('Suhbat yo\'nalishlari')
                                ->required()
                                ->rows(2),
                        ]),
                ]),
        ]);
    }
}
