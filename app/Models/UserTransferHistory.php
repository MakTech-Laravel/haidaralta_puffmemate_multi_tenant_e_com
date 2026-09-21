<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\UserTransferHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property TransferSubjectType $subject_type
 * @property TransferType $transfer_type
 * @property int $from_branch_id
 * @property int $to_branch_id
 * @property string $from_role
 * @property string $to_role
 * @property Carbon $effective_date
 * @property string|null $reason
 * @property int|null $transferred_by
 */
class UserTransferHistory extends Model implements DataTableConfigurable
{
    /** @use HasFactory<UserTransferHistoryFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['reason', 'from_role', 'to_role'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['effective_date', 'created_at', 'id'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['user_id', 'subject_type', 'transfer_type', 'from_branch_id', 'to_branch_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_type',
        'transfer_type',
        'from_branch_id',
        'to_branch_id',
        'from_role',
        'to_role',
        'effective_date',
        'reason',
        'transferred_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_type' => TransferSubjectType::class,
            'transfer_type' => TransferType::class,
            'effective_date' => 'date',
        ];
    }
}
