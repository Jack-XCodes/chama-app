<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChamaStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMembers = User::query()->count();

        return [
            Stat::make('Members', (string) $totalMembers),
        ];
    }
}

