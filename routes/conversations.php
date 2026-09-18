<?php

use App\Http\Controllers\ConversationAttachmentController;
use Illuminate\Support\Facades\Route;

/**
 * Cross-portal conversation routes.
 *
 * Attachments live on the private disk and are shared by every portal, so the
 * download route cannot live in a single portal's file (same reasoning as
 * routes/fcm.php).
 */
Route::middleware(['ensure.any.portal'])->group(function () {
    Route::get('conversation-attachments/{attachment}', [ConversationAttachmentController::class, 'show'])
        ->whereNumber('attachment')
        ->name('conversation-attachments.show');
});
