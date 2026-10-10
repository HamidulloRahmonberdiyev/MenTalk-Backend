<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
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
                ->description('Ism va email Google akkauntidan olinadi va har kirishda yangilanadi.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Ism')->disabled()->dehydrated(false),
                    TextInput::make('email')->label('Email')->disabled()->dehydrated(false),
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
                        ->helperText('Bo\'sh qoldirilsa o\'zgarmaydi. Faqat adminlarga kerak, o\'quvchilar Google orqali kiradi.'),
                ]),
        ]);
    }
}
