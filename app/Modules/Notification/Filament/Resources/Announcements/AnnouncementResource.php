<?php

declare(strict_types=1);

namespace App\Modules\Notification\Filament\Resources\Announcements;

use App\Modules\Notification\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Modules\Notification\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Modules\Notification\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Modules\Notification\Filament\Resources\Announcements\Schemas\AnnouncementForm;
use App\Modules\Notification\Filament\Resources\Announcements\Tables\AnnouncementsTable;
use App\Modules\Notification\Models\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $modelLabel = "e'lon";

    protected static ?string $pluralModelLabel = "E'lonlar";

    protected static string|\UnitEnum|null $navigationGroup = 'Kontent';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    /** Sent text already sits in learners' inboxes; editing it here would only mislead. */
    public static function canEdit(Model $record): bool
    {
        return $record instanceof Announcement && ! $record->isDispatched();
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Announcement && ! $record->isDispatched();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
