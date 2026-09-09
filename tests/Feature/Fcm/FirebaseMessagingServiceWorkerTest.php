<?php

test('service worker omits showNotification when FCM_SW_SHOW_NOTIFICATION is false', function () {
    config(['services.firebase.sw_show_notification' => false]);

    $this->get('/firebase-messaging-sw.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertSee('const SHOW_NOTIFICATION = false;', false)
        ->assertSee('showNotification', false);
});

test('service worker enables showNotification when FCM_SW_SHOW_NOTIFICATION is true', function () {
    config(['services.firebase.sw_show_notification' => true]);

    $this->get('/firebase-messaging-sw.js')
        ->assertOk()
        ->assertSee('const SHOW_NOTIFICATION = true;', false)
        ->assertSee('self.registration.showNotification', false);
});
