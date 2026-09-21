<?php

namespace App\Http\Controllers;

use App\Support\PortalAuth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class WebAppManifestController extends Controller
{
    /**
     * Serve the origin web app manifest (one product name, stable id).
     */
    public function __invoke(): Response
    {
        $name = (string) config('pwa.name');
        $startUrl = $this->startUrl();

        $manifest = [
            'id' => (string) config('pwa.id', '/'),
            'lang' => (string) config('pwa.lang', 'en-AU'),
            'name' => $name,
            'short_name' => (string) config('pwa.short_name', $name),
            'start_url' => $startUrl,
            'scope' => (string) config('pwa.scope', '/'),
            'display' => (string) config('pwa.display', 'standalone'),
            'display_override' => ['standalone', 'browser'],
            'launch_handler' => [
                'client_mode' => 'navigate-existing',
            ],
            'orientation' => (string) config('pwa.orientation', 'any'),
            'background_color' => (string) config('pwa.background_color', '#FFFFFF'),
            'theme_color' => (string) config('pwa.theme_color', '#C4472F'),
            'icons' => config('pwa.icons', []),
            'categories' => config('pwa.categories', ['shopping']),
            'prefer_related_applications' => false,
            'shortcuts' => $this->shortcuts(),
        ];

        $screenshots = $this->screenshots();

        if ($screenshots !== []) {
            $manifest['screenshots'] = $screenshots;
        }

        return response(json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function startUrl(): string
    {
        $guard = PortalAuth::activeGuard();

        if ($guard === null || $guard === 'customer') {
            return '/';
        }

        $user = Auth::guard($guard)->user();
        $user->loadMissing('branch');

        return PortalAuth::dashboardPath($user);
    }

    /**
     * @return list<array{name: string, short_name: string, url: string, icons: list<array{src: string, sizes: string, type: string}>}>
     */
    private function shortcuts(): array
    {
        $icon = [
            [
                'src' => '/pwa/pwa-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
        ];

        $shortcuts = collect(config('pwa.shortcuts', []))
            ->map(fn (array $shortcut): array => [
                'name' => (string) ($shortcut['name'] ?? ''),
                'short_name' => (string) ($shortcut['short_name'] ?? $shortcut['name'] ?? ''),
                'url' => (string) ($shortcut['url'] ?? '/'),
                'icons' => $icon,
            ])
            ->reject(fn (array $shortcut): bool => $shortcut['url'] === '/login')
            ->values()
            ->all();

        $guard = PortalAuth::activeGuard();

        if ($guard === null) {
            $login = collect(config('pwa.shortcuts', []))
                ->first(fn (array $shortcut): bool => ($shortcut['url'] ?? '') === '/login');

            $login = is_array($login) ? $login : [];

            $shortcuts[] = [
                'name' => (string) ($login['name'] ?? 'Log in'),
                'short_name' => (string) ($login['short_name'] ?? $login['name'] ?? 'Log in'),
                'url' => '/login',
                'icons' => $icon,
            ];

            return $shortcuts;
        }

        $user = Auth::guard($guard)->user();
        $user->loadMissing('branch');

        $shortcuts[] = [
            'name' => 'Dashboard',
            'short_name' => 'Dashboard',
            'url' => PortalAuth::dashboardPath($user),
            'icons' => $icon,
        ];

        return $shortcuts;
    }

    /**
     * @return list<array{src: string, sizes: string, type: string, form_factor: string}>
     */
    private function screenshots(): array
    {
        $screenshots = [];

        if (is_file(public_path('pwa/screenshot-narrow.png'))) {
            $screenshots[] = [
                'src' => '/pwa/screenshot-narrow.png',
                'sizes' => '1080x1920',
                'type' => 'image/png',
                'form_factor' => 'narrow',
            ];
        }

        if (is_file(public_path('pwa/screenshot-wide.png'))) {
            $screenshots[] = [
                'src' => '/pwa/screenshot-wide.png',
                'sizes' => '1920x1080',
                'type' => 'image/png',
                'form_factor' => 'wide',
            ];
        }

        return $screenshots;
    }
}
