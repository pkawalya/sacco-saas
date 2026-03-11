<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDocument extends Model
{
    use HasFactory;

    public const TYPE_NATIONAL_ID = 'national_id';

    public const TYPE_PHOTOGRAPH = 'photograph';

    public const TYPE_UTILITY_BILL = 'utility_bill';

    public const TYPE_EMPLOYER_LETTER = 'employer_letter';

    public const TYPE_SIGNATURE_CARD = 'signature_card';

    public const TYPE_APPLICATION_FORM = 'application_form';

    public const TYPES = [
        self::TYPE_NATIONAL_ID => 'National ID',
        self::TYPE_PHOTOGRAPH => 'Photograph',
        self::TYPE_UTILITY_BILL => 'Utility Bill',
        self::TYPE_EMPLOYER_LETTER => 'Employer Letter',
        self::TYPE_SIGNATURE_CARD => 'Signature Card',
        self::TYPE_APPLICATION_FORM => 'Application Form',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'member_id',
        'document_type',
        'file_path',
        'upload_date',
        'expiry_date',
        'verification_status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'upload_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
