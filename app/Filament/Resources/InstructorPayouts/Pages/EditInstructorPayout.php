<?php

namespace App\Filament\Resources\InstructorPayouts\Pages;

use App\Filament\Resources\InstructorPayouts\InstructorPayoutResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInstructorPayout extends EditRecord
{
    protected static string $resource = InstructorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
