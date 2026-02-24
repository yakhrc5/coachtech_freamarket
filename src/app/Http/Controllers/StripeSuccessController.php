<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;

class StripeSuccessController extends Controller
{
    public function success(Request $request)
    {
        $userIdLogin = Auth::id();
        $sessionId = $request->query('session_id');

        Log::info('[stripe-success] hit', [
            'login_user_id' => $userIdLogin,
            'session_id' => $sessionId,
        ]);

        if (!$userIdLogin) {
            Log::warning('[stripe-success] no auth');
            abort(403);
        }

        if (!$sessionId) {
            Log::warning('[stripe-success] no session_id');
            return redirect('/')->with('message', '決済情報が取得できませんでした。');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = CheckoutSession::retrieve($sessionId);

            Log::info('[stripe-success] session', [
                'status' => $session->status ?? null,
                'payment_status' => $session->payment_status ?? null,
                'metadata' => (array)($session->metadata ?? []),
            ]);

            if (($session->status ?? '') !== 'complete') {
                Log::warning('[stripe-success] not complete', ['status' => $session->status ?? null]);
                return redirect('/')->with('message', '決済が完了していません。');
            }

            $meta = $session->metadata ?? [];
            $itemId = (int) ($meta['item_id'] ?? 0);
            $userId = (int) ($meta['user_id'] ?? 0);

            if ($itemId <= 0 || $userId !== (int) $userIdLogin) {
                Log::warning('[stripe-success] invalid meta', [
                    'item_id' => $itemId,
                    'meta_user_id' => $userId,
                    'login_user_id' => $userIdLogin,
                ]);
                return redirect('/')->with('message', '決済情報が不正です。');
            }

            // ★ここ重要：item が実在するかチェック（外部キーや整合の確認）
            $itemExists = Item::whereKey($itemId)->exists();
            Log::info('[stripe-success] item check', ['item_id' => $itemId, 'exists' => $itemExists]);

            if (!$itemExists) {
                return redirect('/')->with('message', '購入対象の商品が見つかりませんでした。');
            }

            $already = Purchase::where('item_id', $itemId)->exists();
            Log::info('[stripe-success] purchase exists?', ['item_id' => $itemId, 'exists' => $already]);

            if (!$already) {
                Purchase::create([
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'payment_method' => (string) ($meta['payment_method'] ?? ''),
                    'postal_code' => (string) ($meta['postal_code'] ?? ''),
                    'address' => (string) ($meta['address'] ?? ''),
                    'building' => (string) ($meta['building'] ?? null),
                ]);
                Log::info('[stripe-success] purchase created', ['item_id' => $itemId, 'user_id' => $userId]);
            }

            session()->forget("purchase.shipping.item_{$itemId}");

            return redirect('/')->with('message', '購入が完了しました。');
        } catch (\Throwable $e) {
            Log::error('[stripe-success] exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/')->with('message', '決済の確定処理でエラーが発生しました。');
        }
    }
}
