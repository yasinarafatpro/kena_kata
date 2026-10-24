<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeGateway implements PaymentGateway
{
    public function createPayment(Order $order): PaymentIntentResult
    {
        $client = new StripeClient(config('payments.stripe.secret'));

        $pi = $client->paymentIntents->create([
            'amount'   => $order->amountMinor(),
            'currency' => strtolower($order->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['order_id' => (string) $order->id],
        ], ['idempotency_key' => "order-{$order->id}-pi"]);

        return new PaymentIntentResult($pi->id, clientSecret: $pi->client_secret);
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('payments.stripe.webhook_secret'),
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            abort(400, 'Invalid signature');
        }

        $o = $event->data->object;
        $orderId = isset($o->metadata['order_id']) ? (int) $o->metadata['order_id'] : null;

        return match ($event->type) {
            'payment_intent.succeeded'      => new WebhookResult($event->id, 'paid', $orderId, $o->id, (int) $o->amount_received),
            'payment_intent.payment_failed' => new WebhookResult($event->id, 'failed', $orderId, $o->id),
            default                         => WebhookResult::ignored($event->id),
        };
    }
}
