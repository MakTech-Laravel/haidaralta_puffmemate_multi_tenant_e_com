<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\UserShiftHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $branch_id
 * @property int|null $previous_shift_template_id
 * @property int $new_shift_template_id
 * @property Carbon|null $previous_start_time
 * @property Carbon|null $previous_end_time
 * @property Carbon $new_start_time
 * @property Carbon $new_end_time
 * @property Carbon $effective_date
 * @property string|null $reason
 * @property string|null $note
 * @property int|null $changed_by
 */
class UserShiftHistory extends Model
{
    /** @use HasFactory<UserShiftHistoryFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'previous_shift_template_id',
        'new_shift_template_id',
        'previous_start_time',
        'previous_end_time',
        'new_start_time',
        'new_end_time',
        'effective_date',
        'reason',
        'note',
        'changed_by',
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
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function previousShiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'previous_shift_template_id');
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function newShiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'new_shift_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_start_time' => 'datetime:H:i',
            'previous_end_time' => 'datetime:H:i',
            'new_start_time' => 'datetime:H:i',
            'new_end_time' => 'datetime:H:i',
            'effective_date' => 'date',
        ];
    }
}
