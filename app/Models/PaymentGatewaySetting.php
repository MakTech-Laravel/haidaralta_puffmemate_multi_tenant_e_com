<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $description
 * @property bool $enabled
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PaymentGatewaySetting extends Model
{
    public const KEY_COD = 'cod';

    public const KEY_POS = 'pos';

    public const KEY_PAYMENT_LINK = 'payment_link';

    /**
     * @var list<string>
     */
    public const KEYS = [
        self::KEY_COD,
        self::KEY_POS,
        self::KEY_PAYMENT_LINK,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'label',
        'description',
        'enabled',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
