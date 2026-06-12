<?php

namespace App\Filament\Resources\InstructorPayouts\Pages;

use App\Filament\Resources\InstructorPayouts\InstructorPayoutResource;
use App\Models\Payout;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewInstructorPayout extends ViewRecord
{
    protected static string $resource = InstructorPayoutResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Instructor Name'),
                TextEntry::make('email')->label('Email'),

                TextEntry::make('total_earned')
                    ->label('Total Earned (EGP)')
                    ->getStateUsing(function (): string {
                        $total = $this->record->ledgerEntries()
                            ->where('type', 'earning')
                            ->sum('amount_piastres');
                        return number_format($total / 100, 2);
                    }),

                TextEntry::make('total_paid')
                    ->label('Total Paid (EGP)')
                    ->getStateUsing(function (): string {
                        $total = Payout::where('instructor_id', $this->record->id)
                            ->where('status', 'paid')
                            ->sum('amount_piastres');
                        return number_format($total / 100, 2);
                    }),

                TextEntry::make('outstanding')
                    ->label('Outstanding (EGP)')
                    ->getStateUsing(function (): string {
                        $earned = $this->record->ledgerEntries()
                            ->where('type', 'earning')
                            ->sum('amount_piastres');
                        $paid = Payout::where('instructor_id', $this->record->id)
                            ->where('status', 'paid')
                            ->sum('amount_piastres');
                        return number_format(($earned - $paid) / 100, 2);
                    }),

                RepeatableEntry::make('payouts')
                    ->label('Payout History')
                    ->schema([
                        TextEntry::make('amount_piastres')
                            ->label('Amount (EGP)')
                            ->getStateUsing(fn ($record) => number_format($record->amount_piastres / 100, 2)),
                        TextEntry::make('status')->label('Status'),
                        TextEntry::make('provider_reference')->label('Reference'),
                        TextEntry::make('paid_at')->label('Paid At')->dateTime(),
                    ]),
            ]);
    }
}
