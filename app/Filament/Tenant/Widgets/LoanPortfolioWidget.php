<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Loan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\QueryException;

class LoanPortfolioWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        try {
            $active = Loan::query()->where('status', Loan::STATUS_ACTIVE);
            $inArrears = (clone $active)->where('days_past_due', '>', 0);

            $totalOutstanding = (clone $active)->sum('total_outstanding');
            $totalInArrears = (clone $active)->sum('amount_in_arrears');
            $activeCount = (clone $active)->count();
            $arrearsCount = $inArrears->count();

            $parRate = $activeCount > 0
                ? round(($arrearsCount / $activeCount) * 100, 1)
                : 0;
        } catch (QueryException) {
            $totalOutstanding = 0;
            $totalInArrears = 0;
            $activeCount = 0;
            $arrearsCount = 0;
            $parRate = 0;
        }

        return [
            Stat::make('Active Loans', number_format($activeCount))
                ->description('Disbursed & running')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Total Outstanding', 'UGX '.number_format($totalOutstanding, 0))
                ->description('Principal + Interest + Penalty')
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('In Arrears', number_format($arrearsCount).' loans')
                ->description('PAR Rate: '.$parRate.'%')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($arrearsCount > 0 ? 'danger' : 'success'),

            Stat::make('Amount in Arrears', 'UGX '.number_format($totalInArrears, 0))
                ->description('Overdue instalments')
                ->icon('heroicon-o-clock')
                ->color($totalInArrears > 0 ? 'warning' : 'success'),
        ];
    }
}
