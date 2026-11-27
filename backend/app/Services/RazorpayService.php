<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Razorpay REST client (orders, signatures, refunds). */
class RazorpayService
{
    private const API = 'https://api.razorpay.com/v1';

    public function keyId(): string
    {
        return (string) Setting::get('razorpay', 'key_id', env('RAZORPAY_KEY_ID'));
    }

    private function secret(): string
    {
        return (string) Setting::get('razorpay', 'key_secret', env('RAZORPAY_KEY_SECRET'));
    }

    private function webhookSecret(): string
    {
        return (string) Setting::get('razorpay', 'webhook_secret', env('RAZORPAY_WEBHOOK_SECRET'));
    }

    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $res = Http::withBasicAuth($this->keyId(), $this->secret())->timeout(15)
            ->post(self::API.'/orders', ['amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => $notes]);
        if (! $res->successful()) {
            throw new RuntimeException('Razorpay order failed: '.$res->body());
        }
        return $res->json();
    }

    public function verifyPayment(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret());
        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $body, string $signature): bool
    {
        $secret = $this->webhookSecret();
        return $secret !== '' && hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }

    public function fetchPayment(string $paymentId): array
    {
        return Http::withBasicAuth($this->keyId(), $this->secret())->get(self::API.'/payments/'.$paymentId)->throw()->json();
    }

    public function refund(string $paymentId, int $amountPaise, array $notes = []): array
    {
        $res = Http::withBasicAuth($this->keyId(), $this->secret())->timeout(15)
            ->post(self::API.'/payments/'.$paymentId.'/refund', ['amount' => $amountPaise, 'notes' => $notes]);
        if (! $res->successful()) {
            throw new RuntimeException('Razorpay refund failed: '.$res->body());
        }
        return $res->json();
    }
}
