<?php

namespace App\Models;

use App\Support\SearchableTimestamp;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, LogsActivity, Searchable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'address',
        'latitude',
        'longitude',
        'is_active',
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
            'address' => $this->address,
            'is_active' => (bool) $this->is_active,
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
            'is_active' => 'boolean',
        ];
    }

    // log activity
    public function getActivitylogOptions(): LogOptions
    {

        // log changes all column
        // return LogOptions::defaults()
        //     ->logAll()
        //     ->logOnlyDirty();

        // log changes only dirty column
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'icon',
                'address',
                'latitude',
                'longitude',
                'is_active',
            ])
            ->logOnlyDirty();

    }
}
