<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class FirebaseMessagingServiceWorkerController extends Controller
{
    /**
     * Serve the origin service worker (FCM + PWA installability). Network-only.
     */
    public function __invoke(): Response
    {
        $headers = [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        if (! config('pwa.sw_enabled')) {
            return response($this->killSwitchScript(), 200, $headers);
        }

        return response($this->fcmScript(), 200, $headers);
    }

    private function killSwitchScript(): string
    {
        return <<<'JS'
/* PWA_SW_ENABLED=false — unregister this origin's worker. */
self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    await self.registration.unregister();
    const windowClients = await self.clients.matchAll({ type: 'window' });
    await Promise.all(windowClients.map((client) => client.navigate(client.url)));
  })());
});
JS;
    }

    private function fcmScript(): string
    {
        $config = [
            'apiKey' => (string) config('services.firebase.api_key'),
            'authDomain' => (string) config('services.firebase.auth_domain'),
            'projectId' => (string) config('services.firebase.project_id'),
            'storageBucket' => (string) config('services.firebase.storage_bucket'),
            'messagingSenderId' => (string) config('services.firebase.messaging_sender_id'),
            'appId' => (string) config('services.firebase.app_id'),
        ];

        if (filled(config('services.firebase.measurement_id'))) {
            $config['measurementId'] = (string) config('services.firebase.measurement_id');
        }

        $json = json_encode($config, JSON_THROW_ON_ERROR);
        $showNotification = config('services.firebase.sw_show_notification') ? 'true' : 'false';
        $version = hash('xxh3', $json.$showNotification.(string) filemtime(__FILE__));

        return <<<JS
/* global importScripts, firebase */
const SW_VERSION = '{$version}';

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

function resolveNotificationClickUrl(data) {
  const nested = data.FCM_MSG && data.FCM_MSG.data ? data.FCM_MSG.data : {};
  const value = data.url || data.link || nested.url || nested.link || '';

  if (!value) {
    return self.location.origin + '/';
  }

  return value;
}

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const rawUrl = resolveNotificationClickUrl(event.notification.data || {});

  event.waitUntil((async () => {
    let target;

    try {
      target = new URL(rawUrl, self.location.origin);
    } catch (error) {
      target = new URL('/', self.location.origin);
    }

    if (target.origin !== self.location.origin) {
      await self.clients.openWindow(target.href);

      return;
    }

    const windowClients = await self.clients.matchAll({
      type: 'window',
      includeUncontrolled: true,
    });

    for (const client of windowClients) {
      if (client.url.startsWith(self.location.origin) && 'focus' in client) {
        await client.focus();

        if ('navigate' in client) {
          await client.navigate(target.href);
        } else {
          await self.clients.openWindow(target.href);
        }

        return;
      }
    }

    await self.clients.openWindow(target.href);
  })());
});

importScripts('https://www.gstatic.com/firebasejs/12.18.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging-compat.js');

firebase.initializeApp({$json});

const messaging = firebase.messaging();
const SHOW_NOTIFICATION = {$showNotification};

messaging.onBackgroundMessage((payload) => {
  if (!SHOW_NOTIFICATION) {
    return;
  }

  const title = payload.notification?.title || payload.data?.title || 'Notification';
  const options = {
    body: payload.notification?.body || payload.data?.body || '',
    data: payload.data || {},
    icon: payload.notification?.icon || '/apple-touch-icon.png',
  };

  self.registration.showNotification(title, options);
});
JS;
    }
}
