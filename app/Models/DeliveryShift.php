<?php

namespace App\Models;

use Database\Factories\DeliveryShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DeliveryShift extends Model
{
    /** @use HasFactory<DeliveryShiftFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active',
        'created_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function startTimeValue(): string
    {
        return $this->start_time->format('H:i:s');
    }

    public function endTimeValue(): string
    {
        return $this->end_time->format('H:i:s');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }
}
