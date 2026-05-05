<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Landing page order / enquiry.
 *
 * @property string $order_number
 * @property string $organization_name
 * @property string $contact_person
 * @property string $email
 * @property string $phone
 * @property string $plan_tier
 * @property string $billing_cycle
 * @property int|null $member_count
 * @property string|null $message
 * @property string $status
 */
class Order extends Model
{
    use CentralConnection;

    protected $fillable = [
        'order_number',
        'organization_name',
        'contact_person',
        'email',
        'phone',
        'plan_tier',
        'billing_cycle',
        'member_count',
        'message',
        'status',
    ];

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.date('ym');
        $last = static::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
