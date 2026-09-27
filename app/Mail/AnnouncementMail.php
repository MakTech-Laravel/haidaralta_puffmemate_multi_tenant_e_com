<?php

namespace App\Mail;

use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\ContactSettingsService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Schema;

class AnnouncementMail extends Mailable
{
    public function __construct(
        public string $title,
        public string $body,
        public string $recipientName,
        public ?string $linkLabel = null,
        public ?string $linkUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.announcements.sent',
            text: 'mail.announcements.sent-text',
            with: $this->viewData(),
        );
    }

    /**
     * @return array{
     *     title: string,
     *     body: string,
     *     recipientName: string,
     *     linkLabel: string|null,
     *     linkUrl: string|null,
     *     themeColor: string,
     *     appName: string,
     *     logoUrl: string|null,
     *     contactEmail: string|null,
     *     contactPhone: string|null,
     *     homeUrl: string
     * }
     */
    private function viewData(): array
    {
        $application = app(ApplicationSettingsService::class);
        $brand = $application->sharedPayload();
        $contacts = app(ContactSettingsService::class)->sharedPayload();

        $appName = (string) ($brand['shortName'] ?: config('app.name', 'PuffMeMate'));

        if (Schema::hasTable('application_settings')) {
            $name = $application->current()->app_name;
            $appName = filled($name) ? $name : $appName;
        }

        $themeColor = filled($brand['themeColor'] ?? null)
            ? (string) $brand['themeColor']
            : '#C4472F';

        return [
            'title' => $this->title,
            'body' => $this->body,
            'recipientName' => $this->recipientName,
            'linkLabel' => $this->linkLabel,
            'linkUrl' => $this->linkUrl,
            'themeColor' => $themeColor,
            'appName' => $appName,
            'logoUrl' => $this->absoluteUrl(is_string($brand['logoUrl'] ?? null) ? $brand['logoUrl'] : null),
            'contactEmail' => filled($contacts['email'] ?? null) ? (string) $contacts['email'] : null,
            'contactPhone' => filled($contacts['phone'] ?? null) ? (string) $contacts['phone'] : null,
            'homeUrl' => url('/'),
        ];
    }

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
