<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\ContactInquiryStatus;
use App\Enums\ContactInquiryType;
use App\Models\Concerns\HasDataTableConfig;
use App\Services\Storefront\ContactInquiryService;
use Database\Factories\ContactInquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $full_name
 * @property string $phone
 * @property string|null $email
 * @property ContactInquiryType $type
 * @property string $message
 * @property ContactInquiryStatus $status
 * @property Carbon|null $read_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ContactInquiry extends Model implements DataTableConfigurable
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['full_name', 'email', 'phone'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['full_name', 'type', 'status', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status', 'type'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'type',
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
            'type' => ContactInquiryType::class,
            'status' => ContactInquiryStatus::class,
            'read_at' => 'datetime',
        ];
    }

    public function markRead(): void
    {
        app(ContactInquiryService::class)->markRead($this);
    }
}
