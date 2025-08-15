<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationPreferences extends Component
{
    public $preferences = [];
    public $globalSettings = [
        'email_enabled' => true,
        'in_app_enabled' => true,
        'digest_enabled' => false,
        'digest_frequency' => 'weekly',
    ];

    protected $rules = [
        'preferences.*.in_app_enabled' => 'boolean',
        'preferences.*.email_enabled' => 'boolean',
        'preferences.*.digest_enabled' => 'boolean',
        'preferences.*.digest_frequency' => 'in:daily,weekly,monthly',
        'globalSettings.email_enabled' => 'boolean',
        'globalSettings.in_app_enabled' => 'boolean',
        'globalSettings.digest_enabled' => 'boolean',
        'globalSettings.digest_frequency' => 'in:daily,weekly,monthly',
    ];

    public function mount()
    {
        $this->loadPreferences();
        $this->loadGlobalSettings();
    }

    public function loadPreferences()
    {
        $user = Auth::user();
        $userPreferences = NotificationPreference::getPreferencesForUser($user);
        
        $this->preferences = [];
        
        foreach (Notification::getNotificationTypes() as $type => $display) {
            $preference = $userPreferences->get($type);
            
            $this->preferences[$type] = [
                'in_app_enabled' => $preference ? $preference->in_app_enabled : true,
                'email_enabled' => $preference ? $preference->email_enabled : true,
                'digest_enabled' => $preference ? $preference->digest_enabled : false,
                'digest_frequency' => $preference ? $preference->digest_frequency : 'weekly',
            ];
        }
    }

    public function loadGlobalSettings()
    {
        $user = Auth::user();
        
        // Load global settings from user preferences or set defaults
        $this->globalSettings = [
            'email_enabled' => true,
            'in_app_enabled' => true,
            'digest_enabled' => false,
            'digest_frequency' => 'weekly',
        ];
    }

    public function savePreferences()
    {
        $this->validate();

        $user = Auth::user();
        
        // Update individual notification type preferences
        NotificationPreference::updatePreferencesForUser($user, $this->preferences);
        
        session()->flash('message', 'Notification preferences saved successfully!');
    }

    public function enableAllInApp()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['in_app_enabled'] = true;
        }
    }

    public function disableAllInApp()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['in_app_enabled'] = false;
        }
    }

    public function enableAllEmail()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['email_enabled'] = true;
        }
    }

    public function disableAllEmail()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['email_enabled'] = false;
        }
    }

    public function enableAllDigest()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['digest_enabled'] = true;
            $this->preferences[$type]['digest_frequency'] = $this->globalSettings['digest_frequency'];
        }
    }

    public function disableAllDigest()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type]['digest_enabled'] = false;
        }
    }

    public function applyGlobalDigestFrequency()
    {
        foreach ($this->preferences as $type => $settings) {
            if ($this->preferences[$type]['digest_enabled']) {
                $this->preferences[$type]['digest_frequency'] = $this->globalSettings['digest_frequency'];
            }
        }
    }

    public function resetToDefaults()
    {
        foreach ($this->preferences as $type => $settings) {
            $this->preferences[$type] = [
                'in_app_enabled' => true,
                'email_enabled' => true,
                'digest_enabled' => false,
                'digest_frequency' => 'weekly',
            ];
        }
    }

    public function getNotificationTypeDescription(string $type): string
    {
        return match($type) {
            Notification::TYPE_PAYMENT_STATUS => 'Get notified when your payment status changes (approved, rejected, etc.)',
            Notification::TYPE_DOCUMENT_UPLOAD => 'Get notified when new documents are uploaded to the group',
            Notification::TYPE_FINANCIAL_REPORT => 'Get notified when new financial reports are available',
            Notification::TYPE_ANNOUNCEMENT => 'Get notified about group announcements and important updates',
            Notification::TYPE_ACCOUNT_STATUS => 'Get notified about changes to your account or membership status',
            Notification::TYPE_TRANSACTION_CREATED => 'Get notified about new transactions in the group',
            Notification::TYPE_VERIFICATION_REQUIRED => 'Get notified when transactions require your verification (treasurers only)',
            Notification::TYPE_MEMBER_JOINED => 'Get notified when new members join the group',
            Notification::TYPE_SYSTEM_MAINTENANCE => 'Get notified about system maintenance and updates',
            default => 'Receive notifications for this type of event',
        };
    }

    public function getNotificationTypeIcon(string $type): string
    {
        return match($type) {
            Notification::TYPE_PAYMENT_STATUS => '💳',
            Notification::TYPE_DOCUMENT_UPLOAD => '📄',
            Notification::TYPE_FINANCIAL_REPORT => '📊',
            Notification::TYPE_ANNOUNCEMENT => '📢',
            Notification::TYPE_ACCOUNT_STATUS => '👤',
            Notification::TYPE_TRANSACTION_CREATED => '💰',
            Notification::TYPE_VERIFICATION_REQUIRED => '✅',
            Notification::TYPE_MEMBER_JOINED => '👥',
            Notification::TYPE_SYSTEM_MAINTENANCE => '🔧',
            default => '🔔',
        };
    }

    public function render()
    {
        return view('livewire.notification-preferences', [
            'notificationTypes' => Notification::getNotificationTypes(),
            'digestFrequencies' => NotificationPreference::getDigestFrequencies(),
        ]);
    }
}