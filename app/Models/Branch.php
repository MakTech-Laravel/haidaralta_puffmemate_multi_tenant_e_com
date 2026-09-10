<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Support\SearchableTimestamp;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use AuditsChanges, HasFactory, Searchable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'logo',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'opening_time',
        'closing_time',
        'status',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'address' => $this->address,
            'status' => (bool) $this->status,
            'created_at' => SearchableTimestamp::from($this->created_at),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'status' => 'boolean',
        ];
    }
}
