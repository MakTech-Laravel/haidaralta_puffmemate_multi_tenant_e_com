<?php

namespace App\Http\Requests\Platform;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementSendMode;
use App\Enums\AnnouncementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
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
        $individual = $this->input('send_mode') === AnnouncementSendMode::Individual->value;

        return [
            'send_mode' => ['required', Rule::enum(AnnouncementSendMode::class)],
            'audiences' => $individual
                ? ['nullable', 'array']
                : ['required', 'array', 'min:1'],
            'audiences.*' => [Rule::enum(AnnouncementAudience::class)],
            'recipients' => $individual
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'recipients.*.id' => ['required', 'integer', 'exists:users,id'],
            'recipients.*.audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'type' => ['required', Rule::enum(AnnouncementType::class)],
            'icon' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'link_label' => ['nullable', 'string', 'max:120'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'via_phone' => ['sometimes', 'boolean'],
            'via_email' => ['sometimes', 'boolean'],
            'via_push' => ['sometimes', 'boolean'],
        ];
    }

    public function sendMode(): AnnouncementSendMode
    {
        return AnnouncementSendMode::from((string) $this->validated('send_mode'));
    }

    /**
     * @return list<AnnouncementAudience>
     */
    public function audiences(): array
    {
        $values = $this->validated('audiences') ?? [];

        return array_values(array_map(
            fn (string $audience): AnnouncementAudience => AnnouncementAudience::from($audience),
            $values,
        ));
    }

    /**
     * @return list<array{id: int, audience: AnnouncementAudience}>
     */
    public function recipients(): array
    {
        $people = $this->validated('recipients') ?? [];

        return array_values(array_map(
            fn (array $person): array => [
                'id' => (int) $person['id'],
                'audience' => AnnouncementAudience::from($person['audience']),
            ],
            $people,
        ));
    }

    public function type(): AnnouncementType
    {
        return AnnouncementType::from((string) $this->validated('type'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'via_phone' => $this->boolean('via_phone'),
            'via_email' => $this->boolean('via_email'),
            'via_push' => $this->boolean('via_push'),
            'link_label' => $this->filled('link_label') ? $this->input('link_label') : null,
            'link_url' => $this->filled('link_url') ? $this->input('link_url') : null,
        ]);
    }
}
