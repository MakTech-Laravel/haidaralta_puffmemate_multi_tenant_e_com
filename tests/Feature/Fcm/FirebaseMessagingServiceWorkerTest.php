<?php

test('service worker omits showNotification when FCM_SW_SHOW_NOTIFICATION is false', function () {
    config(['services.firebase.sw_show_notification' => false]);

    $this->get('/firebase-messaging-sw.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertHeader('Service-Worker-Allowed', '/')
        ->assertSee('const SHOW_NOTIFICATION = false;', false)
        ->assertSee('showNotification', false)
        ->assertSee('const SW_VERSION', false)
        ->assertSee('self.skipWaiting()', false)
        ->assertSee('notificationclick', false)
        ->assertDontSee('caches.open')
        ->assertDontSee('workbox');
});

test('service worker enables showNotification when FCM_SW_SHOW_NOTIFICATION is true', function () {
    config(['services.firebase.sw_show_notification' => true]);

    $this->get('/firebase-messaging-sw.js')
        ->assertOk()
        ->assertSee('const SHOW_NOTIFICATION = true;', false)
        ->assertSee('self.registration.showNotification', false);
});

test('service worker kill-switch unregisters without caching', function () {
    config(['pwa.sw_enabled' => false]);

    $this->get('/firebase-messaging-sw.js')
        ->assertOk()
        ->assertHeader('Service-Worker-Allowed', '/')
        ->assertSee('self.registration.unregister()', false)
        ->assertSee('self.skipWaiting()', false)
        ->assertDontSee('importScripts')
        ->assertDontSee('caches.open')
        ->assertDontSee('workbox');
});
