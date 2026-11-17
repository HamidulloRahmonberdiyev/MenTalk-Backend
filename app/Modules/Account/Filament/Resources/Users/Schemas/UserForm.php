<?php

declare(strict_types=1);

namespace App\Modules\Account\Filament\Resources\Users\Schemas;

use App\Modules\Account\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profil')
                ->description('Ism va email Google yoki Telegram akkauntidan olinadi.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Ism')->disabled()->dehydrated(false),
                    TextInput::make('email')->label('Email')->disabled()->dehydrated(false)
                        ->visible(fn (User $record) => $record->email !== null),
                    TextInput::make('telegram_username')->label('Telegram')->prefix('@')->disabled()->dehydrated(false)
                        ->visible(fn (User $record) => $record->telegram_id !== null),
                    TextInput::make('phone')->label('Telefon')->disabled()->dehydrated(false)
                        ->helperText('Telegram tasdiqlagan raqam, har kirishda yangilanadi.')
                        ->visible(fn (User $record) => $record->phone !== null),
                ]),

            Section::make('Admin kirishi')
                ->columns(2)
                ->schema([
                    Toggle::make('is_admin')
                        ->label('Admin paneliga kirish huquqi')
                        ->disabled(fn (User $record) => $record->getKey() === auth()->id())
                        ->helperText('O\'zingizning huquqingizni o\'zingiz olib tashlay olmaysiz.'),
                    TextInput::make('password')
                        ->label('Yangi parol')
                        ->password()
                        ->revealable()
                        ->minLength(12)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText('Bo\'sh qoldirilsa o\'zgarmaydi. Faqat adminlarga kerak, o\'quvchilar Google yoki Telegram orqali kiradi.'),
                ]),
        ]);
    }
}
