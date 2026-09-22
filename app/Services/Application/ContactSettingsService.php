<?php

namespace App\Services\Application;

use App\Models\ContactSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ContactSettingsService
{
    public const CACHE_KEY = 'contact_settings.current';

    public function current(): ContactSetting
    {
        /** @var array<string, mixed> $attributes */
        $attributes = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return $this->resolveOrCreate()->getAttributes();
        });

        return (new ContactSetting)->newFromBuilder($attributes);
    }

    public function refresh(): ContactSetting
    {
        Cache::forget(self::CACHE_KEY);

        $settings = $this->resolveOrCreate();
        Cache::forever(self::CACHE_KEY, $settings->getAttributes());

        return $settings;
    }

    /**
     * @param  array{
     *     phone?: string|null,
     *     fax?: string|null,
     *     email?: string|null,
     *     whatsapp?: string|null,
     *     address?: string|null,
     *     map_embed_url?: string|null,
     *     socials?: array<int, array{key: string, label: string, url: string|null, icon?: string|null}>
     * }  $data
     */
    public function update(array $data, ?int $updatedBy = null): ContactSetting
    {
        $settings = $this->current();

        $settings->phone = $data['phone'] ?? null;
        $settings->fax = $data['fax'] ?? null;
        $settings->email = $data['email'] ?? null;
        $settings->whatsapp = $data['whatsapp'] ?? null;
        $settings->address = $data['address'] ?? null;
        $settings->map_embed_url = $data['map_embed_url'] ?? null;
        $settings->socials = $this->normalizeSocials($data['socials'] ?? []);
        $settings->updated_by = $updatedBy;
        $settings->save();

        return $this->refresh();
    }

    /**
     * @return array{
     *     phone: string|null,
     *     fax: string|null,
     *     email: string|null,
     *     whatsapp: string|null,
     *     address: string|null,
     *     map_embed_url: string|null,
     *     socials: array<int, array{key: string, label: string, url: string, icon: string|null}>
     * }
     */
    public function formPayload(): array
    {
        return $this->sharedPayload();
    }

    /**
     * @return array{
     *     phone: string|null,
     *     fax: string|null,
     *     email: string|null,
     *     whatsapp: string|null,
     *     address: string|null,
     *     map_embed_url: string|null,
     *     socials: array<int, array{key: string, label: string, url: string, icon: string|null}>
     * }
     */
    public function sharedPayload(): array
    {
        if (! $this->tableReady()) {
            return $this->emptyPayload();
        }

        $settings = $this->current();

        return [
            'phone' => $settings->phone,
            'fax' => $settings->fax,
            'email' => $settings->email,
            'whatsapp' => $settings->whatsapp,
            'address' => $settings->address,
            'map_embed_url' => $settings->map_embed_url,
            'socials' => $this->normalizeSocials($settings->socials ?? []),
        ];
    }

    private function resolveOrCreate(): ContactSetting
    {
        $existing = ContactSetting::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return ContactSetting::query()->create([
            'phone' => '+1 012 3456 789',
            'fax' => null,
            'email' => 'demo@gmail.com',
            'whatsapp' => null,
            'address' => '132 Dartmouth Street Boston, Massachusetts 02156 United States',
            'map_embed_url' => null,
            'socials' => [
                ['key' => 'twitter', 'label' => 'Twitter', 'url' => '', 'icon' => 'Twitter'],
                ['key' => 'instagram', 'label' => 'Instagram', 'url' => '', 'icon' => 'Instagram'],
                ['key' => 'discord', 'label' => 'Discord', 'url' => '', 'icon' => 'MessageCircle'],
            ],
        ]);
    }

    /**
     * @param  array<int, mixed>  $socials
     * @return array<int, array{key: string, label: string, url: string, icon: string|null}>
     */
    private function normalizeSocials(array $socials): array
    {
        $normalized = [];

        foreach ($socials as $social) {
            if (! is_array($social)) {
                continue;
            }

            $key = trim((string) ($social['key'] ?? ''));
            $label = trim((string) ($social['label'] ?? ''));
            $url = trim((string) ($social['url'] ?? ''));
            $icon = trim((string) ($social['icon'] ?? ''));

            if ($key === '' && $label === '' && $url === '' && $icon === '') {
                continue;
            }

            $normalized[] = [
                'key' => $key !== '' ? $key : strtolower(str_replace(' ', '-', $label)),
                'label' => $label !== '' ? $label : $key,
                'url' => $url,
                'icon' => $icon !== '' ? $icon : null,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @return array{
     *     phone: null,
     *     fax: null,
     *     email: null,
     *     whatsapp: null,
     *     address: null,
     *     map_embed_url: null,
     *     socials: array{}
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'phone' => null,
            'fax' => null,
            'email' => null,
            'whatsapp' => null,
            'address' => null,
            'map_embed_url' => null,
            'socials' => [],
        ];
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('contact_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
