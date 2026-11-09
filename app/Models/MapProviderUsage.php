<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string $capability
 * @property string $used_on Y-m-d
 * @property int $request_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MapProviderUsage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'capability',
        'used_on',
        'request_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_count' => 'integer',
        ];
    }
}
