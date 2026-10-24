<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function createPayment(Order $order): PaymentIntentResult;

    /** Must verify the provider's signature itself and abort(400) when it is invalid. */
    public function handleWebhook(Request $request): WebhookResult;
}
