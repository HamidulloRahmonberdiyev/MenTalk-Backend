<?php

declare(strict_types=1);

namespace App\Modules\Notification\Filament\Resources\Announcements\Schemas;

use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class AnnouncementForm
{
    private const LOCALES = ['uz' => "O'zbekcha", 'ru' => 'Русский', 'en' => 'English'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asosiy')
                ->columns(3)
                ->schema([
                    Select::make('kind')
                        ->label('Turi')
                        ->helperText('Ilovadagi ikonka va rangni belgilaydi.')
                        ->options(collect(NotificationKind::cases())->mapWithKeys(fn (NotificationKind $kind) => [$kind->value => $kind->label()]))
                        ->default(NotificationKind::News->value)
                        ->required(),
                    Select::make('action')
                        ->label('Tugma')
                        ->helperText("Bosilganda qaysi ekran ochiladi. Bo'sh qolsa, tugma bo'lmaydi.")
                        ->options(collect(NotificationAction::cases())->mapWithKeys(fn (NotificationAction $action) => [$action->value => $action->label()])),
                    Select::make('audience')
                        ->label('Kimga')
                        ->helperText("Ilova tili bo'yicha. Bo'sh qolsa — hammaga.")
                        ->placeholder('Barcha foydalanuvchilar')
                        ->options(collect(InterfaceLanguage::cases())->mapWithKeys(fn (InterfaceLanguage $language) => [
                            $language->value => self::LOCALES[$language->value].' tilidagilar',
                        ])),
                ]),

            Section::make('Matn')
                ->description("O'zbekcha majburiy. Boshqa tilda yozilmasa, o'sha tildagi foydalanuvchi o'zbekchasini ko'radi.")
                ->schema([
                    Tabs::make('locales')->tabs(
                        collect(self::LOCALES)->map(fn (string $name, string $locale) => Tab::make($name)->schema([
                            TextInput::make("title.{$locale}")->label('Sarlavha')->required($locale === 'uz')->maxLength(80),
                            Textarea::make("body.{$locale}")->label('Matn')->required($locale === 'uz')->maxLength(300)->rows(3),
                        ]))->values()->all(),
                    ),
                ]),
        ]);
    }
}
