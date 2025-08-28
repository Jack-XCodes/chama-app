<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Filament\Support\Contracts\TranslatableContentDriver;

class QuickActionsWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static ?int $sort = 3;
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function sendAnnouncement(): Action
    {
        return Action::make('sendAnnouncement')
            ->label('Send Announcement')
            ->icon('heroicon-o-megaphone')
            ->size(ActionSize::Large)
            ->url(route('admin.announcements'))
            ->visible(auth()->user()->can('manage-users'));
    }

    public function generateReports(): Action
    {
        return Action::make('generateReports')
            ->label('Generate Reports')
            ->icon('heroicon-o-document-chart-bar')
            ->size(ActionSize::Large)
            ->url(route('reports.generate'))
            ->visible(auth()->user()->can('manage-finances'));
    }

    public function inviteMembers(): Action
    {
        return Action::make('inviteMembers')
            ->label('Invite Members')
            ->icon('heroicon-o-user-plus')
            ->size(ActionSize::Large)
            ->url(route('admin.users.invite'))
            ->visible(auth()->user()->can('manage-users'));
    }

    public function viewReconciliation(): Action
    {
        return Action::make('viewReconciliation')
            ->label('Payment Queue')
            ->icon('heroicon-o-currency-dollar')
            ->size(ActionSize::Large)
            ->url(route('treasurer.payments'))
            ->visible(auth()->user()->can('manage-finances'));
    }

    public function manageMembers(): Action
    {
        return Action::make('manageMembers')
            ->label('Manage Members')
            ->icon('heroicon-o-users')
            ->size(ActionSize::Large)
            ->url(route('admin.users'))
            ->visible(auth()->user()->can('manage-users'));
    }

    protected function getActions(): array
    {
        return [
            $this->sendAnnouncement(),
            $this->generateReports(),
            $this->inviteMembers(),
            $this->viewReconciliation(),
            $this->manageMembers(),
        ];
    }

    public function render(): View
    {
        return view('filament.widgets.quick-actions-widget');
    }
}