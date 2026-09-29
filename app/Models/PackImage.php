<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Support\MediaUrl;
use Database\Factories\PackImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pack_id
 * @property string $path
 * @property int $sort_order
 * @property-read Pack|null $pack
 */
class PackImage extends Model
{
    /** @use HasFactory<PackImageFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pack_id',
        'path',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    public function url(): string
    {
        return MediaUrl::resolve($this->path) ?? MediaUrl::DEFAULT_FALLBACK;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
