<?php

namespace App\Http\Requests\Notifications;

use App\Support\PortalAuth;
use Illuminate\Foundation\Http\FormRequest;

class OwnsUserNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PortalAuth::activeGuard() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
