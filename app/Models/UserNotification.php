<?php

namespace App\Models;

use Database\Factories\UserNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;


class UserNotification extends Model
{

    use HasFactory, SoftDeletes;


    protected $fillable = [
        'notification_id',
        'user_id',
        'read_at',
    ];


    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }


    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
