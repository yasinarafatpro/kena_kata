<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class PayPalGateway implements PaymentGateway
{
    private function http(): PendingRequest
    {
        $c = config('payments.paypal');

        $token = Cache::remember('paypal_access_token', now()->addMinutes(30), fn () =>
            Http::asForm()->withBasicAuth((string) $c['client_id'], (string) $c['secret'])
                ->post($c['base_url'] . '/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()->json('access_token'));

        return Http::baseUrl($c['base_url'])->withToken($token)->acceptJson()->timeout(15);
    }

    public function createPayment(Order $order): PaymentIntentResult
    {
        $front = rtrim((string) config('payments.frontend_url'), '/');

        $res = $this->http()->withHeaders(['PayPal-Request-Id' => "order-{$order->id}"])
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $order->id,
                    'custom_id'    => (string) $order->id,
                    'amount' => [
                        'currency_code' => $order->currency,
                        'value'         => number_format($order->amountMinor() / 100, 2, '.', ''),
                    ],
                ]],
                'payment_source' => ['paypal' => ['experience_context' => [
                    'user_action' => 'PAY_NOW',
                    'return_url'  => "$front/orders/{$order->id}",
                    'cancel_url'  => "$front/checkout",
                ]]],
            ])->throw()->json();

        $url = collect($res['links'])->first(fn ($l) => in_array($l['rel'], ['payer-action', 'approve']))['href']
            ?? throw new \RuntimeException('No PayPal approval link returned');

        return new PaymentIntentResult($res['id'], redirectUrl: $url);
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        // Caveat: PayPal verifies against the exact original body. If valid events fail
        // verification, send the raw JSON string instead of the decoded array.
        $event = json_decode($request->getContent(), true) ?: [];

        $status = $this->http()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url'          => $request->header('PAYPAL-CERT-URL'),
            'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id'        => config('payments.paypal.webhook_id'),
            'webhook_event'     => $event,
        ])->json('verification_status');

        abort_unless($status === 'SUCCESS', 400, 'Invalid signature');

        $id  = (string) ($event['id'] ?? '');
        $res = $event['resource'] ?? [];

        return match ($event['event_type'] ?? '') {
            'CHECKOUT.ORDER.APPROVED'   => $this->capture((string) $res['id'], $id),
            'PAYMENT.CAPTURE.COMPLETED' => new WebhookResult(
                $id, 'paid', (int) ($res['custom_id'] ?? 0),
                $res['supplementary_data']['related_ids']['order_id'] ?? null,
                (int) round(((float) $res['amount']['value']) * 100),
            ),
            'PAYMENT.CAPTURE.DENIED'    => new WebhookResult($id, 'failed', (int) ($res['custom_id'] ?? 0)),
            default                     => WebhookResult::ignored($id),
        };
    }

    /** Buyer approved → capture the funds. "Paid" is only recorded on PAYMENT.CAPTURE.COMPLETED. */
    private function capture(string $paypalOrderId, string $eventId): WebhookResult
    {
        $this->http()->withHeaders(['PayPal-Request-Id' => "capture-$paypalOrderId"])
            ->withBody('{}', 'application/json')
            ->post("/v2/checkout/orders/$paypalOrderId/capture")->throw();

        return WebhookResult::ignored($eventId);
    }
}
