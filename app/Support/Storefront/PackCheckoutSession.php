<?php

namespace App\Support\Storefront;

use App\Models\Pack;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Session-backed Packs & Saves buy-now checkout (no cart).
 */
class PackCheckoutSession
{
    public const SESSION_KEY = 'storefront.checkout_pack';

    /**
     * @return array{pack_id: int, quantity: int}|null
     */
    public static function get(Request $request): ?array
    {
        $payload = $request->session()->get(self::SESSION_KEY);

        if (! is_array($payload)) {
            return null;
        }

        $packId = $payload['pack_id'] ?? null;
        $quantity = $payload['quantity'] ?? null;

        if (! is_numeric($packId) || ! is_numeric($quantity) || (int) $quantity < 1) {
            return null;
        }

        return [
            'pack_id' => (int) $packId,
            'quantity' => (int) $quantity,
        ];
    }

    public static function put(Request $request, Pack $pack, int $quantity): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'pack_id' => $pack->id,
            'quantity' => $quantity,
        ]);
        $request->session()->forget('storefront.checkout_coupon');
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public static function has(Request $request): bool
    {
        return self::get($request) !== null;
    }

    /**
     * Resolve the active pack from session, or forget invalid state.
     */
    public static function resolveActivePack(Request $request): ?Pack
    {
        $session = self::get($request);

        if ($session === null) {
            return null;
        }

        $pack = StorefrontCatalog::findActivePackById($session['pack_id']);

        if ($pack === null || $pack->quantity < $session['quantity']) {
            self::forget($request);

            return null;
        }

        return $pack;
    }

    /**
     * @throws ValidationException
     */
    public static function requireActivePack(Request $request): array
    {
        $session = self::get($request);

        if ($session === null) {
            throw ValidationException::withMessages([
                'pack' => 'This pack checkout has expired. Choose a pack again.',
            ]);
        }

        $pack = StorefrontCatalog::findActivePackById($session['pack_id']);

        if ($pack === null) {
            self::forget($request);

            throw ValidationException::withMessages([
                'pack' => 'This pack is no longer available.',
            ]);
        }

        if ($pack->quantity < $session['quantity']) {
            self::forget($request);

            throw ValidationException::withMessages([
                'quantity' => 'Only '.$pack->quantity.' pack(s) left.',
            ]);
        }

        return [
            'pack' => $pack,
            'quantity' => $session['quantity'],
        ];
    }
}
