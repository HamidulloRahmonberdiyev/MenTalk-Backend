<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\Scenarios\Pages;

use App\Modules\Conversation\Filament\Resources\Scenarios\ScenarioResource;
use App\Modules\Conversation\Models\Scenario;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditScenario extends EditRecord
{
    protected static string $resource = ScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Scenario $record) => $record->conversations()->exists()),
        ];
    }
}
