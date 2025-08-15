<?php

namespace App\Filament\Widgets;

use App\Models\FinancialReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Exports\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class QuickActionsWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static ?int $sort = 3;
    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public function sendAnnouncement(): Action
    {
        return Action::make('sendAnnouncement')
            ->label('Send Group Announcement')
            ->icon('heroicon-m-megaphone')
            ->color('warning')
            ->size(ActionSize::Large)
            ->form([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Announcement Title'),
                
                RichEditor::make('content')
                    ->required()
                    ->placeholder('Announcement Content'),
                
                Grid::make(2)
                    ->schema([
                        Toggle::make('is_urgent')
                            ->label('Mark as Urgent')
                            ->default(false),
                        
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ]),
                
                Grid::make(2)
                    ->schema([
                        Toggle::make('send_email')
                            ->label('Send Email')
                            ->default(true),
                        
                        Toggle::make('send_in_app')
                            ->label('Send In-App')
                            ->default(true),
                    ]),
                
                Select::make('target_roles')
                    ->multiple()
                    ->label('Target Roles')
                    ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                    ->placeholder('All Members'),
            ])
            ->action(function (array $data): void {
                $announcement = \App\Models\Announcement::create([
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'is_urgent' => $data['is_urgent'],
                    'priority' => $data['priority'],
                    'send_email' => $data['send_email'],
                    'send_in_app' => $data['send_in_app'],
                    'target_roles' => $data['target_roles'] ?? null,
                    'created_by' => auth()->id(),
                    'is_published' => true,
                    'published_at' => now(),
                ]);

                $announcement->publish();

                Notification::make()
                    ->success()
                    ->title('Announcement Sent')
                    ->body('Your announcement has been sent successfully.')
                    ->send();
            });
    }

    public function generateMonthlyReports(): Action
    {
        return Action::make('generateMonthlyReports')
            ->label('Generate Monthly Reports')
            ->icon('heroicon-m-document-chart-bar')
            ->color('success')
            ->size(ActionSize::Large)
            ->form([
                Select::make('report_types')
                    ->multiple()
                    ->label('Select Reports')
                    ->options(FinancialReport::getReportTypes())
                    ->required(),
                
                Select::make('format')
                    ->label('Export Format')
                    ->options(FinancialReport::getExportFormats())
                    ->default('pdf')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $exportService = new ReportExportService();
                $startDate = now()->startOfMonth();
                $endDate = now();
                $reports = [];

                foreach ($data['report_types'] as $type) {
                    try {
                        $report = $exportService->generateReport(
                            $type,
                            $startDate,
                            $endDate,
                            $data['format']
                        );
                        $reports[] = $report;
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Generating Report')
                            ->body("Failed to generate {$type} report: {$e->getMessage()}")
                            ->send();
                    }
                }

                if (!empty($reports)) {
                    Notification::make()
                        ->success()
                        ->title('Reports Generated')
                        ->body(count($reports) . ' reports have been generated successfully.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Reports')
                                ->url(route('reports.archive'))
                                ->button(),
                        ])
                        ->send();
                }
            });
    }

    public function inviteNewMember(): Action
    {
        return Action::make('inviteNewMember')
            ->label('Invite New Member')
            ->icon('heroicon-m-user-plus')
            ->color('primary')
            ->size(ActionSize::Large)
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Member Name'),
                
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Email Address')
                    ->unique(User::class),
                
                Select::make('roles')
                    ->multiple()
                    ->label('Assign Roles')
                    ->options(\Spatie\Permission\Models\Role::pluck('name', 'name')),
            ])
            ->action(function (array $data): void {
                $password = \Str::random(12);
                
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => \Hash::make($password),
                ]);

                if (!empty($data['roles'])) {
                    $user->assignRole($data['roles']);
                }

                // Send invitation email with temporary password
                \Mail::to($user->email)->send(new \App\Mail\MemberInvitation($user, $password));

                Notification::make()
                    ->success()
                    ->title('Member Invited')
                    ->body('An invitation has been sent to ' . $user->email)
                    ->send();
            });
    }

    public function viewReconciliationQueue(): Action
    {
        $pendingCount = Transaction::where('status', Transaction::STATUS_PENDING)
            ->orWhere('status', Transaction::STATUS_REQUIRES_VERIFICATION)
            ->count();

        return Action::make('viewReconciliationQueue')
            ->label('View Reconciliation Queue')
            ->badge($pendingCount)
            ->icon('heroicon-m-banknotes')
            ->color($pendingCount > 0 ? 'warning' : 'gray')
            ->size(ActionSize::Large)
            ->url(route('treasurer.payments'));
    }

    public function memberManagement(): Action
    {
        return Action::make('memberManagement')
            ->label('Member Management')
            ->icon('heroicon-m-users')
            ->color('info')
            ->size(ActionSize::Large)
            ->url(route('admin.members'));
    }

    protected function getViewData(): array
    {
        return [
            'actions' => [
                $this->sendAnnouncement(),
                $this->generateMonthlyReports(),
                $this->inviteNewMember(),
                $this->viewReconciliationQueue(),
                $this->memberManagement(),
            ],
        ];
    }

    public function render(): View
    {
        return view('filament.widgets.quick-actions-widget', $this->getViewData());
    }
}