<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\Auditable;
use Database\Factories\Tenant\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $member_number
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $status
 * @property int $kyc_score
 */
class Member extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return MemberFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->member_number)) {
                $member->member_number = static::generateMemberNumber();
            }
        });
    }

    /**
     * Generate member number based on tenant format.
     */
    public static function generateMemberNumber(): string
    {
        $format = tenancy()->tenant->member_number_format ?? 'MEM-{year}{sequence:6}';
        $year = date('y'); // 2 digits
        $sequence = static::count() + 1; // Next sequence

        $number = str_replace('{year}', $year, $format);
        $number = preg_replace_callback('/\{sequence:(\d+)\}/', function ($matches) use ($sequence) {
            return str_pad($sequence, (int) $matches[1], '0', STR_PAD_LEFT);
        }, $number);

        return $number;
    }

    public const STATUS_APPLICANT = 'applicant';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DORMANT = 'dormant';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DECEASED = 'deceased';

    public const STATUS_EXITED = 'exited';

    public const STATUSES = [
        self::STATUS_APPLICANT,
        self::STATUS_ACTIVE,
        self::STATUS_DORMANT,
        self::STATUS_SUSPENDED,
        self::STATUS_DECEASED,
        self::STATUS_EXITED,
    ];

    protected $fillable = [
        'member_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'national_id_type',
        'national_id_number',
        'photo_path',
        'physical_address',
        'village',
        'cell',
        'district',
        'postal_address',
        'primary_phone',
        'secondary_phone',
        'email',
        'occupation',
        'employer_name',
        'monthly_income_range',
        'nok_name',
        'nok_relationship',
        'nok_contact',
        'nok_gender',
        'nok_national_id_number',
        'nok_national_id_document',
        'nok_marital_status',
        'member_intention',
        'willing_weekly_savings_amount',
        'member_category',
        'referral_source',
        'kyc_score',
        'kyc_threshold',
        'branch_code',
        'status',
        'registered_by',
        'approved_by',
        'approved_at',
        'dormant_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'kyc_score' => 'integer',
            'kyc_threshold' => 'integer',
            'approved_at' => 'datetime',
            'dormant_at' => 'datetime',
            'willing_weekly_savings_amount' => 'decimal:2',
        ];
    }

    // ─── Accessors ──────────────────────────────

    /**
     * Full name accessor.
     */
    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    /**
     * Whether KYC is complete (score >= threshold).
     */
    public function getIsKycCompleteAttribute(): bool
    {
        return $this->kyc_score >= $this->kyc_threshold;
    }

    // ─── Relationships ──────────────────────────

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    public function shares(): HasOne
    {
        return $this->hasOne(MemberShare::class);
    }

    public function stateHistory(): HasMany
    {
        return $this->hasMany(MemberStateHistory::class)->orderByDesc('transitioned_at');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class, 'member_group_member')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function savingsAccounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function fixedDeposits(): HasMany
    {
        return $this->hasMany(FixedDeposit::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    // ─── Scopes ─────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive($query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeByBranch($query, string $branchCode): void
    {
        $query->where('branch_code', $branchCode);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeKycIncomplete($query): void
    {
        $query->whereColumn('kyc_score', '<', 'kyc_threshold');
    }

    // ─── Business Logic ─────────────────────────

    /**
     * Transition the member to a new lifecycle state (FR-MM-012).
     */
    public function transitionTo(string $newState, ?string $reasonCode = null, ?string $notes = null, ?int $actedBy = null): void
    {
        $oldState = $this->status;

        $this->update(['status' => $newState]);

        $this->stateHistory()->create([
            'from_state' => $oldState,
            'to_state' => $newState,
            'reason_code' => $reasonCode,
            'notes' => $notes,
            'acted_by' => $actedBy,
            'transitioned_at' => now(),
        ]);
    }

    /**
     * Check if the member can exit (FR-MM-010).
     *
     * @return array<int, string> List of blocking reasons
     */
    public function getExitBlockReasons(): array
    {
        $blocks = [];

        // Placeholder hooks for when loan/savings modules are built
        // if ($this->loans()->where('status', 'active')->exists()) {
        //     $blocks[] = 'Outstanding loan balance';
        // }

        $shares = $this->shares;
        if ($shares && $shares->total_value > 0) {
            $blocks[] = 'Unsettled share obligations (UGX '.number_format($shares->total_value).')';
        }

        return $blocks;
    }

    /**
     * Recalculate KYC score based on verified documents (FR-MM-007).
     */
    public function recalculateKycScore(): void
    {
        $weights = [
            'national_id' => 30,
            'photograph' => 20,
            'utility_bill' => 15,
            'employer_letter' => 15,
            'signature_card' => 10,
            'application_form' => 10,
        ];

        $verifiedTypes = $this->documents()
            ->where('verification_status', 'verified')
            ->pluck('document_type')
            ->toArray();

        $score = 0;
        foreach ($weights as $type => $weight) {
            if (in_array($type, $verifiedTypes, true)) {
                $score += $weight;
            }
        }

        $this->update(['kyc_score' => min($score, 100)]);
    }
}
