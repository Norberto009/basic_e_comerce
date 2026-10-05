<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\MarkOrderAsPaidRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Marca una orden como pagada manualmente (efectivo, transferencia, etc.).
     * El dueno de la orden no puede marcar su propia orden como pagada.
     */
    public function markAsPaid(MarkOrderAsPaidRequest $request, Order $order): OrderResource|JsonResponse
    {
        if ($order->status === 'paid') {
            return response()->json([
                'message' => 'This order has already been paid.',
            ], 422);
        }

        if ($order->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot mark your own order as paid manually. This action must be performed by another authenticated account.',
            ], 403);
        }

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'status' => 'succeeded',
                'amount' => $order->total,
                'currency' => $order->currency,
                'metadata' => [
                    'manual' => true,
                    'marked_by' => $request->user()->id,
                    'payment_method' => $request->validated('payment_method'),
                    'note' => $request->validated('note'),
                ],
            ]
        );

        $order->update(['status' => 'paid']);
        $order->load('items.product', 'payment');

        return new OrderResource($order);
    }
}
