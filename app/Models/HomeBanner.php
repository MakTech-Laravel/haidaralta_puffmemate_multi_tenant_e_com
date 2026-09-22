<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sort_order
 * @property bool $is_active
 * @property string $eyebrow
 * @property string $title
 * @property string $description
 * @property string|null $image_path
 * @property string $primary_cta_label
 * @property string $primary_cta_href
 * @property string $secondary_cta_label
 * @property string $secondary_cta_href
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HomeBanner extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sort_order',
        'is_active',
        'eyebrow',
        'title',
        'description',
        'image_path',
        'primary_cta_label',
        'primary_cta_href',
        'secondary_cta_label',
        'secondary_cta_href',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
