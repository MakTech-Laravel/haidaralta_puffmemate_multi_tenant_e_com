<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $phone
 * @property string|null $fax
 * @property string|null $email
 * @property string|null $whatsapp
 * @property string|null $address
 * @property string|null $map_embed_url
 * @property array<int, array{key: string, label: string, url: string, icon?: string|null}>|null $socials
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ContactSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'fax',
        'email',
        'whatsapp',
        'address',
        'map_embed_url',
        'socials',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'socials' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
