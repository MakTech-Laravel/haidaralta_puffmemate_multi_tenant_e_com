<?php

namespace App\Models;

use Database\Factories\DeliveryMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryMethod extends Model
{
    /** @use HasFactory<DeliveryMethodFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'label',
        'description',
        'fee',
        'requires_slot',
        'status',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'requires_slot' => 'boolean',
            'status' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return HasMany<DeliveryTimeSlot, $this>
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(DeliveryTimeSlot::class);
    }
}
