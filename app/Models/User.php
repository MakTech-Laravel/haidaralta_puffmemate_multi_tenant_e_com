<?php

namespace App\Models;

use App\Contracts\ScoutSearchable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CustomerAddressType;
use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasFcmDeviceTokens;
use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\MediaUrl;
use App\Support\SearchableTimestamp;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property Carbon|null $phone_verified_at
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property UserType $user_type
 * @property CustomerRegistrationStatus|null $registration_status
 * @property int|null $branch_id
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch|null $branch
 * @property-read Address|null $savedAddress
 * @property-read CustomerAddress|null $shippingAddress
 * @property-read CustomerAddress|null $billingAddress
 */
#[Fillable(['name', 'phone', 'email', 'password', 'user_type', 'registration_status', 'branch_id', 'is_active', 'phone_verified_at', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasMedia, PasskeyUser, ScoutSearchable
{
    /** @use HasFactory<UserFactory> */
    use AuditsChanges, HasFactory, HasFcmDeviceTokens, HasRoles, InteractsWithAppMedia, Notifiable, PasskeyAuthenticatable, Searchable, TwoFactorAuthenticatable;

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection('avatar');
    }

    public function avatarUrl(?string $fallback = null): ?string
    {
        return MediaUrl::fromCollection($this, 'avatar', $fallback);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasOne<DriverProfile, $this>
     */
    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    /**
     * The single delivery address checkout pre-fills, including its map pin.
     *
     * @return HasOne<Address, $this>
     */
    public function savedAddress(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return HasOne<CustomerAddress, $this>
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('type', CustomerAddressType::Shipping);
    }

    /**
     * @return HasOne<CustomerAddress, $this>
     */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('type', CustomerAddressType::Billing);
    }

    /**
     * @return HasMany<DriverBranchAssignment, $this>
     */
    public function driverBranchAssignments(): HasMany
    {
        return $this->hasMany(DriverBranchAssignment::class, 'driver_id');
    }

    /**
     * @return HasMany<DriverRoster, $this>
     */
    public function driverRosters(): HasMany
    {
        return $this->hasMany(DriverRoster::class, 'driver_id');
    }

    /**
     * @return HasMany<UserTransferHistory, $this>
     */
    public function transferHistories(): HasMany
    {
        return $this->hasMany(UserTransferHistory::class);
    }

    /**
     * @return HasMany<UserShiftAssignment, $this>
     */
    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(UserShiftAssignment::class);
    }

    /**
     * @return HasMany<UserShiftDay, $this>
     */
    public function shiftDays(): HasMany
    {
        return $this->hasMany(UserShiftDay::class);
    }

    /**
     * @return HasMany<UserShiftHistory, $this>
     */
    public function shiftHistories(): HasMany
    {
        return $this->hasMany(UserShiftHistory::class);
    }

    /**
     * @return HasMany<UserNotification, $this>
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'user_type' => $this->user_type->value,
            'branch_id' => $this->branch_id !== null ? (int) $this->branch_id : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => SearchableTimestamp::from($this->created_at),
        ];
    }

    public function isPlatform(): bool
    {
        return $this->user_type === UserType::PLATFORM;
    }

    public function isBranch(): bool
    {
        return $this->user_type === UserType::BRANCH;
    }

    public function isDriver(): bool
    {
        return $this->user_type === UserType::DRIVER;
    }

    public function isCustomer(): bool
    {
        return $this->user_type === UserType::CUSTOMER;
    }

    public function isRegisteredCustomer(): bool
    {
        return $this->isCustomer()
            && $this->registration_status === CustomerRegistrationStatus::Registered;
    }

    public function guardName(): string
    {
        return $this->user_type->guard();
    }

    /**
     * Passkey username display / WebAuthn user name.
     */
    public function getPasskeyUsername(): string
    {
        return $this->phone;
    }

    protected function getDefaultGuardName(): string
    {
        return $this->guardName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'registration_status' => CustomerRegistrationStatus::class,
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
