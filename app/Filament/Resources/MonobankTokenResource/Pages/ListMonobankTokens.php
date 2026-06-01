<?php

namespace App\Filament\Resources\MonobankTokenResource\Pages;

use App\Filament\Resources\MonobankTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonobankTokens extends ListRecords
{
    protected static string $resource = MonobankTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Додати токен'),
        ];
    }
}
