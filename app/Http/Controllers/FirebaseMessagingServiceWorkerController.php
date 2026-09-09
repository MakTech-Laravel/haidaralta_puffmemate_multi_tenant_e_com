<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class FirebaseMessagingServiceWorkerController extends Controller
{
    /**
     * Serve the FCM service worker with public web config from env.
     */
    public function __invoke(): Response
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

        $script = <<<JS
/* global importScripts, firebase */
importScripts('https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js');

firebase.initializeApp({$json});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const title = payload.notification?.title || payload.data?.title || 'Notification';
  const options = {
    body: payload.notification?.body || payload.data?.body || '',
    data: payload.data || {},
    icon: payload.notification?.icon || '/apple-touch-icon.png',
  };

  self.registration.showNotification(title, options);
});
JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
