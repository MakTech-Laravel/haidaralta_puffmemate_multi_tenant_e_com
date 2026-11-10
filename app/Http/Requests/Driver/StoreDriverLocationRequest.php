<?php

namespace App\Http\Requests\Driver;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreDriverLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'heading' => ['nullable', 'integer', 'between:0,360'],
            'recorded_at' => ['nullable', 'date'],
            // Seconds since the device took the fix: immune to a wrong device clock.
            'age_seconds' => ['nullable', 'numeric', 'min:0', 'max:3600'],
        ];
    }

    /**
     * The reading in the shape the location service expects. A missing or future device clock is
     * replaced with the server time so a wrong phone clock cannot park a position in the future.
     *
     * @return array{latitude: float, longitude: float, accuracy: float|null, speed: float|null, heading: int|null, recorded_at: CarbonInterface}
     */
    public function reading(): array
    {
        $recordedAt = match (true) {
            $this->filled('age_seconds') => now()->subSeconds((int) round((float) $this->input('age_seconds'))),
            $this->filled('recorded_at') => Carbon::parse($this->input('recorded_at')),
            default => now(),
        };

        if ($recordedAt->isFuture()) {
            $recordedAt = now();
        }

        return [
            'latitude' => (float) $this->input('latitude'),
            'longitude' => (float) $this->input('longitude'),
            'accuracy' => $this->filled('accuracy') ? (float) $this->input('accuracy') : null,
            'speed' => $this->filled('speed') ? (float) $this->input('speed') : null,
            'heading' => $this->filled('heading') ? (int) $this->input('heading') : null,
            'recorded_at' => $recordedAt,
        ];
    }
}
