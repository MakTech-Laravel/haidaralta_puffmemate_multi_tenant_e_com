<?php

namespace App\Services\Fcm;

use App\Models\FcmDeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FcmPushService
{
    public function __construct(private Messaging $messaging) {}

    public function sendToToken(string $token, FcmPushPayload $payload): FcmSendResult
    {
        return $this->sendToTokens([$token], $payload);
    }

    /**
     * @param  iterable<int, string>  $tokens
     */
    public function sendToTokens(iterable $tokens, FcmPushPayload $payload): FcmSendResult
    {
        $unique = collect($tokens)
            ->filter(fn (mixed $token): bool => is_string($token) && trim($token) !== '')
            ->map(fn (string $token): string => trim($token))
            ->unique()
            ->values();

        if ($unique->isEmpty()) {
            return new FcmSendResult;
        }

        $sent = 0;
        $failed = 0;
        $removed = 0;
        $errors = [];

        foreach ($unique as $token) {
            try {
                $this->messaging->send($this->buildMessage($token, $payload));
                $sent++;
            } catch (NotFound $exception) {
                $failed++;
                $errors[$token] = $exception->getMessage();

                if ($this->forgetToken($token)) {
                    $removed++;
                }
            } catch (MessagingException $exception) {
                $failed++;
                $errors[$token] = $exception->getMessage();

                if ($this->shouldForgetToken($exception) && $this->forgetToken($token)) {
                    $removed++;
                }
            } catch (Throwable $exception) {
                $failed++;
                $errors[$token] = $exception->getMessage();
            }
        }

        return new FcmSendResult(
            sent: $sent,
            failed: $failed,
            invalidTokensRemoved: $removed,
            errors: $errors,
        );
    }

    public function sendToUser(User $user, FcmPushPayload $payload): FcmSendResult
    {
        return $this->sendToTokens($user->routeFcmNotificationTokens(), $payload);
    }

    /**
     * @param  iterable<int, User|int|string>  $usersOrIds
     */
    public function sendToUsers(iterable $usersOrIds, FcmPushPayload $payload): FcmSendResult
    {
        $ids = $this->normalizeUserIds($usersOrIds);

        if ($ids->isEmpty()) {
            return new FcmSendResult;
        }

        $tokens = FcmDeviceToken::query()
            ->whereIn('user_id', $ids->all())
            ->pluck('token')
            ->all();

        return $this->sendToTokens($tokens, $payload);
    }

    /**
     * @param  iterable<int, int|string>  $userIds
     */
    public function sendToUserIds(iterable $userIds, FcmPushPayload $payload): FcmSendResult
    {
        return $this->sendToUsers($userIds, $payload);
    }

    private function buildMessage(string $token, FcmPushPayload $payload): CloudMessage
    {
        $notification = Notification::create($payload->title, $payload->body);

        if ($payload->imageUrl) {
            $notification = $notification->withImageUrl($payload->imageUrl);
        }

        $message = CloudMessage::new()
            ->withToken($token)
            ->withNotification($notification);

        $data = $payload->stringData();

        if ($data !== []) {
            $message = $message->withData($data);
        }

        return $message;
    }

    private function forgetToken(string $token): bool
    {
        return FcmDeviceToken::query()->where('token', $token)->delete() > 0;
    }

    private function shouldForgetToken(MessagingException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unregistered')
            || str_contains($message, 'registration-token-not-registered')
            || str_contains($message, 'invalid-registration-token')
            || str_contains($message, 'not found');
    }

    /**
     * @param  iterable<int, User|int|string>  $usersOrIds
     * @return Collection<int, int>
     */
    private function normalizeUserIds(iterable $usersOrIds): Collection
    {
        return collect($usersOrIds)
            ->map(function (User|int|string $value): ?int {
                if ($value instanceof User) {
                    return (int) $value->id;
                }

                if (is_numeric($value)) {
                    return (int) $value;
                }

                return null;
            })
            ->filter(fn (?int $id): bool => $id !== null && $id > 0)
            ->unique()
            ->values();
    }
}
