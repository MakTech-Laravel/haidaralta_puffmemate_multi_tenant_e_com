<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\WholesaleInquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $full_name
 * @property string $phone
 * @property string|null $company
 * @property string|null $email
 * @property string $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $message
 * @property WholesaleInquiryStatus $status
 * @property Carbon|null $read_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WholesaleInquiry extends Model implements DataTableConfigurable
{
    /** @use HasFactory<WholesaleInquiryFactory> */
    use HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['full_name', 'email', 'phone'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['full_name', 'status', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone',
        'company',
        'email',
        'address',
        'latitude',
        'longitude',
        'message',
        'status',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WholesaleInquiryStatus::class,
            'read_at' => 'datetime',
        ];
    }

    public function markRead(): void
    {
        if ($this->status === WholesaleInquiryStatus::Read) {
            return;
        }

        $this->forceFill([
            'status' => WholesaleInquiryStatus::Read,
            'read_at' => now(),
        ])->save();
    }
}
