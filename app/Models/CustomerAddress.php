<?php

namespace App\Models;

use App\Enums\AustralianState;
use App\Enums\CustomerAddressType;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property CustomerAddressType $type
 * @property string|null $name
 * @property string|null $address
 * @property AustralianState|null $region
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $email
 * @property string|null $phone
 * @property-read User|null $user
 */
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'address',
        'region',
        'city',
        'zip',
        'email',
        'phone',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomerAddressType::class,
            'region' => AustralianState::class,
        ];
    }
}
