<?php

namespace App\Filament\Resources\MonobankTokenResource\Pages;

use App\Filament\Resources\MonobankTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonobankToken extends EditRecord
{
    protected static string $resource = MonobankTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Видалити'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
