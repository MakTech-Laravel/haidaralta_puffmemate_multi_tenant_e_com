<?php

namespace App\Services\Application;

use App\Models\SmtpSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SmtpSettingsService
{
    public const CACHE_KEY = 'smtp_settings.current';

    public function current(): SmtpSetting
    {
        /** @var array<string, mixed> $attributes */
        $attributes = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return $this->resolveOrCreate()->getAttributes();
        });

        return (new SmtpSetting)->newFromBuilder($attributes);
    }

    public function refresh(): SmtpSetting
    {
        Cache::forget(self::CACHE_KEY);

        $settings = $this->resolveOrCreate();
        Cache::forever(self::CACHE_KEY, $settings->getAttributes());
        $this->bootRuntimeOverlay($settings);

        return $settings;
    }

    public function bootRuntimeOverlay(?SmtpSetting $settings = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        $settings ??= $this->current();

        if (! $settings->enabled) {
            return;
        }

        $encryption = $settings->encryption;
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->host);
        Config::set('mail.mailers.smtp.port', $settings->port);
        Config::set('mail.mailers.smtp.username', $settings->username);
        Config::set('mail.mailers.smtp.password', $settings->password);
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);

        if (filled($settings->from_address)) {
            Config::set('mail.from.address', $settings->from_address);
            Config::set('backup.notifications.mail.from.address', $settings->from_address);
        }

        if (filled($settings->from_name)) {
            Config::set('mail.from.name', $settings->from_name);
            Config::set('backup.notifications.mail.from.name', $settings->from_name);
        }

        if (filled($settings->reply_to_address)) {
            Config::set('mail.reply_to.address', $settings->reply_to_address);
            Config::set(
                'mail.reply_to.name',
                filled($settings->from_name)
                    ? $settings->from_name
                    : (string) config('app.name'),
            );
        } else {
            Config::set('mail.reply_to.address', null);
            Config::set('mail.reply_to.name', null);
        }
    }

    /**
     * @param  array{
     *     enabled: bool,
     *     host?: string|null,
     *     port?: int|null,
     *     encryption?: string|null,
     *     username?: string|null,
     *     password?: string|null,
     *     from_address?: string|null,
     *     from_name?: string|null,
     *     reply_to_address?: string|null,
     * }  $data
     */
    public function update(array $data, ?int $updatedBy = null): SmtpSetting
    {
        $settings = $this->current();

        $settings->enabled = (bool) $data['enabled'];
        $settings->host = $data['host'] ?? null;
        $settings->port = isset($data['port']) ? (int) $data['port'] : null;
        $settings->encryption = $data['encryption'] ?? null;
        $settings->username = $data['username'] ?? null;
        $settings->from_address = $data['from_address'] ?? null;
        $settings->from_name = $data['from_name'] ?? null;
        $settings->reply_to_address = $data['reply_to_address'] ?? null;
        $settings->updated_by = $updatedBy;

        if (array_key_exists('password', $data) && filled($data['password'])) {
            $settings->password = (string) $data['password'];
        }

        $settings->save();

        return $this->refresh();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     host: string|null,
     *     port: int|null,
     *     encryption: string|null,
     *     username: string|null,
     *     has_password: bool,
     *     from_address: string|null,
     *     from_name: string|null,
     *     reply_to_address: string|null
     * }
     */
    public function formPayload(): array
    {
        $settings = $this->current();

        return [
            'enabled' => $settings->enabled,
            'host' => $settings->host,
            'port' => $settings->port,
            'encryption' => $settings->encryption ?? 'tls',
            'username' => $settings->username,
            'has_password' => filled($settings->password),
            'from_address' => $settings->from_address,
            'from_name' => $settings->from_name,
            'reply_to_address' => $settings->reply_to_address,
        ];
    }

    public function sendTestEmail(string $to): void
    {
        $previousMailer = config('mail.default');

        $this->bootRuntimeOverlay();

        // Feature tests force the array transport after overlaying SMTP credentials.
        if (app()->environment('testing') && $previousMailer === 'array') {
            Config::set('mail.default', 'array');
        }

        $settings = $this->current();
        $appName = (string) config('app.name', 'PuffMeMate');

        Mail::raw(
            "This is a test email from {$appName} SMTP settings.",
            function ($message) use ($to, $appName, $settings): void {
                $message->to($to)->subject("{$appName} SMTP test");

                if (filled($settings->reply_to_address)) {
                    $message->replyTo($settings->reply_to_address);
                }
            },
        );
    }

    private function resolveOrCreate(): SmtpSetting
    {
        $existing = SmtpSetting::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return SmtpSetting::query()->create([
            'enabled' => false,
            'host' => config('mail.mailers.smtp.host'),
            'port' => (int) config('mail.mailers.smtp.port', 587),
            'encryption' => 'tls',
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ]);
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('smtp_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
