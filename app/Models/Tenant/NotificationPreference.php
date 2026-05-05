<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Member notification preference per event/channel (FR-AN-011, FR-AN-012).
 *
 * @property int $id
 * @property int $member_id
 * @property string $event_type
 * @property string $channel
 * @property bool $is_enabled
 * @property string $language
 */
class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    // ─── Event Types ────────────────────────────────────────────
    public const EVENT_LOAN_DISBURSEMENT = 'loan_disbursement';

    public const EVENT_PAYMENT_RECEIVED = 'payment_received';

    public const EVENT_PAYMENT_DUE = 'payment_due';

    public const EVENT_ACCOUNT_ALERT = 'account_alert';

    public const EVENT_DEPOSIT_CONFIRMED = 'deposit_confirmed';

    public const EVENT_WITHDRAWAL_CONFIRMED = 'withdrawal_confirmed';

    public const EVENT_PROMOTIONAL = 'promotional';

    public const EVENT_TYPES = [
        self::EVENT_LOAN_DISBURSEMENT => 'Loan Disbursement',
        self::EVENT_PAYMENT_RECEIVED => 'Payment Received',
        self::EVENT_PAYMENT_DUE => 'Payment Due',
        self::EVENT_ACCOUNT_ALERT => 'Account Alert',
        self::EVENT_DEPOSIT_CONFIRMED => 'Deposit Confirmed',
        self::EVENT_WITHDRAWAL_CONFIRMED => 'Withdrawal Confirmed',
        self::EVENT_PROMOTIONAL => 'Promotional',
    ];

    // ─── Channels ───────────────────────────────────────────────
    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PUSH = 'push';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNELS = [
        self::CHANNEL_SMS => 'SMS',
        self::CHANNEL_EMAIL => 'Email',
        self::CHANNEL_PUSH => 'Push Notification',
        self::CHANNEL_WHATSAPP => 'WhatsApp',
    ];

    // ─── Languages ──────────────────────────────────────────────
    public const LANGUAGES = [
        'en' => 'English',
        'lg' => 'Luganda',
        'sw' => 'Swahili',
    ];

    protected $fillable = [
        'member_id',
        'event_type',
        'channel',
        'is_enabled',
        'language',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }
}
