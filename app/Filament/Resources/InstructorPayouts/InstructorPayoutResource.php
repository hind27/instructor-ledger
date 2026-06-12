<?php

namespace App\Filament\Resources\InstructorPayouts;

use App\Filament\Resources\InstructorPayouts\Pages\ListInstructorPayouts;
use App\Filament\Resources\InstructorPayouts\Pages\ViewInstructorPayout;
use App\Models\Payout;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstructorPayoutResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Instructor Payouts';

    protected static ?string $modelLabel = 'Instructor';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Instructor')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email'),

                TextColumn::make('total_earned')
                    ->label('Total Earned (EGP)')
                    ->getStateUsing(function (User $record): string {
                        $total = $record->ledgerEntries()
                            ->where('type', 'earning')
                            ->sum('amount_piastres');
                        return number_format($total / 100, 2);
                    }),

                TextColumn::make('total_paid')
                    ->label('Total Paid (EGP)')
                    ->getStateUsing(function (User $record): string {
                        $total = Payout::where('instructor_id', $record->id)
                            ->where('status', 'paid')
                            ->sum('amount_piastres');
                        return number_format($total / 100, 2);
                    }),

                TextColumn::make('outstanding')
                    ->label('Outstanding (EGP)')
                    ->getStateUsing(function (User $record): string {
                        $earned = $record->ledgerEntries()
                            ->where('type', 'earning')
                            ->sum('amount_piastres');
                        $paid = Payout::where('instructor_id', $record->id)
                            ->where('status', 'paid')
                            ->sum('amount_piastres');
                        return number_format(($earned - $paid) / 100, 2);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructorPayouts::route('/'),
            'view'  => ViewInstructorPayout::route('/{record}'),
        ];
    }
}
