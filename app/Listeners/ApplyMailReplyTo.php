<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

class ApplyMailReplyTo
{
    /**
     * Apply the global reply-to from config (overlaid by SMTP settings) to every
     * outbound message that does not already define one.
     */
    public function handle(MessageSending $event): void
    {
        $address = config('mail.reply_to.address');

        if (! filled($address)) {
            return;
        }

        $symfonyMessage = $event->message;

        if ($symfonyMessage->getReplyTo() !== []) {
            return;
        }

        $name = config('mail.reply_to.name');

        $symfonyMessage->replyTo(
            filled($name)
                ? new Address((string) $address, (string) $name)
                : new Address((string) $address),
        );
    }
}
