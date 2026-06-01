<?php

namespace App\Filament\Resources\MonobankTokenResource\Pages;

use App\Filament\Resources\MonobankTokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonobankToken extends CreateRecord
{
    protected static string $resource = MonobankTokenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
