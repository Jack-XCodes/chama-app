@extends('emails.notifications.layout')

@section('content')
<div class="greeting">
    Hello {{ $user->name }}!
</div>

<div class="content">
    <p>Here's your {{ $frequency }} digest of notifications from {{ $period['description'] }}.</p>
    
    <!-- Summary Stats -->
    <div class="highlight-box">
        <h3 style="margin-top: 0; color: #8B4513;">📊 Summary</h3>
        <table style="width: 100%; margin: 0;">
            <tr>
                <td style="padding: 8px 0; width: 50%;">
                    <strong>Total Notifications:</strong> {{ $summary['total'] }}
                </td>
                <td style="padding: 8px 0;">
                    @if($summary['urgent'] > 0)
                        <span style="color: #dc3545;"><strong>🚨 {{ $summary['urgent'] }} Urgent</strong></span>
                    @elseif($summary['high'] > 0)
                        <span style="color: #ffc107;"><strong>⚠️ {{ $summary['high'] }} High Priority</strong></span>
                    @else
                        <span style="color: #28a745;">✅ No urgent items</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0;">
                    <strong>Period:</strong> {{ $period['start'] }} - {{ $period['end'] }}
                </td>
                <td style="padding: 8px 0;">
                    @if($summary['most_common_type'])
                        <strong>Most Common:</strong> {{ \App\Models\Notification::getNotificationTypes()[$summary['most_common_type']] ?? 'Unknown' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if($summary['urgent'] > 0)
    <!-- Urgent Notifications -->
    <div class="urgent-box">
        <h3 style="margin-top: 0; color: #856404;">🚨 Urgent Notifications</h3>
        @foreach($notifications->where('priority', \App\Models\Notification::PRIORITY_URGENT) as $notification)
        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #ffeaa7;">
            <div style="font-weight: 600; color: #856404;">{{ $notification->title }}</div>
            <div style="margin: 5px 0; font-size: 14px;">{{ $notification->message }}</div>
            <div style="font-size: 12px; color: #6c757d;">
                {{ $notification->created_at->format('M j, Y g:i A') }}
                @if($notification->action_url)
                    | <a href="{{ $notification->action_url }}" style="color: #8B4513;">{{ $notification->action_text ?? 'View Details' }}</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Notifications by Type -->
    @foreach($groupedNotifications as $type => $typeNotifications)
    <div style="margin: 30px 0;">
        <h3 style="color: #8B4513; border-bottom: 2px solid #8B4513; padding-bottom: 10px; margin-bottom: 20px;">
            {{ $this->getNotificationTypeIcon($type) }} {{ \App\Models\Notification::getNotificationTypes()[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}
            <span style="font-size: 14px; font-weight: normal; color: #6c757d;">({{ $typeNotifications->count() }} notifications)</span>
        </h3>

        @foreach($typeNotifications->take(5) as $notification)
        <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid {{ $this->getPriorityColor($notification->priority) }};">
            <div style="font-weight: 600; margin-bottom: 5px;">{{ $notification->title }}</div>
            <div style="margin-bottom: 10px; color: #495057;">{{ \Str::limit($notification->message, 120) }}</div>
            <div style="font-size: 12px; color: #6c757d; display: flex; justify-content: space-between; align-items: center;">
                <span>{{ $notification->created_at->diffForHumans() }}</span>
                @if($notification->action_url)
                    <a href="{{ $notification->action_url }}" style="color: #8B4513; text-decoration: none; font-weight: 500;">
                        {{ $notification->action_text ?? 'View Details' }} →
                    </a>
                @endif
            </div>
        </div>
        @endforeach

        @if($typeNotifications->count() > 5)
        <div style="text-align: center; margin-top: 15px;">
            <a href="{{ config('app.url') }}/notifications?type={{ $type }}" class="button-secondary" style="font-size: 14px; padding: 10px 20px;">
                View {{ $typeNotifications->count() - 5 }} More {{ \App\Models\Notification::getNotificationTypes()[$type] ?? 'Notifications' }}
            </a>
        </div>
        @endif
    </div>
    @endforeach

    <!-- Quick Actions -->
    <div style="text-align: center; margin: 40px 0;">
        <a href="{{ config('app.url') }}/notifications" class="button">
            View All Notifications
        </a>
        <a href="{{ config('app.url') }}/dashboard" class="button-secondary">
            Go to Dashboard
        </a>
    </div>

    <!-- Preference Management -->
    <div style="margin-top: 40px; padding: 20px; background-color: #f8f9fa; border-radius: 6px; text-align: center;">
        <h4 style="margin-top: 0; color: #495057;">Manage Your Notifications</h4>
        <p style="margin-bottom: 15px; color: #6c757d; font-size: 14px;">
            You're receiving this {{ $frequency }} digest because you've enabled digest emails in your preferences.
        </p>
        <a href="{{ config('app.url') }}/notification-preferences" style="color: #8B4513; text-decoration: none; font-weight: 500;">
            Update Notification Preferences
        </a>
    </div>
</div>

@php
function getNotificationTypeIcon($type) {
    return match($type) {
        \App\Models\Notification::TYPE_PAYMENT_STATUS => '💳',
        \App\Models\Notification::TYPE_DOCUMENT_UPLOAD => '📄',
        \App\Models\Notification::TYPE_FINANCIAL_REPORT => '📊',
        \App\Models\Notification::TYPE_ANNOUNCEMENT => '📢',
        \App\Models\Notification::TYPE_ACCOUNT_STATUS => '👤',
        \App\Models\Notification::TYPE_TRANSACTION_CREATED => '💰',
        \App\Models\Notification::TYPE_VERIFICATION_REQUIRED => '✅',
        \App\Models\Notification::TYPE_MEMBER_JOINED => '👥',
        \App\Models\Notification::TYPE_SYSTEM_MAINTENANCE => '🔧',
        default => '🔔',
    };
}

function getPriorityColor($priority) {
    return match($priority) {
        \App\Models\Notification::PRIORITY_LOW => '#6c757d',
        \App\Models\Notification::PRIORITY_NORMAL => '#007bff',
        \App\Models\Notification::PRIORITY_HIGH => '#ffc107',
        \App\Models\Notification::PRIORITY_URGENT => '#dc3545',
        default => '#007bff',
    };
}
@endphp
@endsection