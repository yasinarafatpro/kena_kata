<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

/** Cash on delivery: nothing to collect online. An admin confirms the order from the dashboard. */
final class CodGateway implements PaymentGateway
{
    public function createPayment(Order $order): PaymentIntentResult
    {
        return new PaymentIntentResult('cod-' . $order->id);
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        abort(404);
    }
}
