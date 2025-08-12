<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdvancedPaymentController extends Controller
{
    /**
     * Initialize payment with external gateway
     */
    public function initializePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string',
            //'gateway' => 'required|string|in:stripe,paystack,flutterwave'
        ]);

        $order = Order::with(['user', 'items'])->find($request->order_id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'payment_method_id' => $this->getPaymentMethodId($request->payment_method),
            'transaction_id' => 'TXN-' . Str::upper(Str::random(12)),
            'amount' => $order->total_amount,
            'status' => 'initialized',
            //'gateway' => $request->gateway,
           // 'gateway_response' => []
        ]);

        // Initialize payment based on gateway
        $paymentData = $this->initializeGatewayPayment($order, $transaction, $request->gateway);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'payment_data' => $paymentData
            ]
        ]);
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(Request $request, $gateway)
    {
        $payload = $request->all();

        Log::info("Webhook received from {$gateway}", $payload);

        switch ($gateway) {
            case 'stripe':
                return $this->handleStripeWebhook($payload);
            case 'paystack':
                return $this->handlePaystackWebhook($payload);
            case 'flutterwave':
                return $this->handleFlutterwaveWebhook($payload);
            default:
                return response()->json(['error' => 'Invalid gateway'], 400);
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
            'refund_type' => 'required|in:full,partial'
        ]);

        $order = Order::find($request->order_id);
        $transaction = Transaction::where('order_id', $order->id)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No completed transaction found for this order'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($request->refund_type === 'partial' && $request->amount > $transaction->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed transaction amount'
            ], Response::HTTP_BAD_REQUEST);
        }

        $refundAmount = $request->refund_type === 'full' ? $transaction->amount : $request->amount;

        $refund = Refund::create([
            'transaction_id' => $transaction->id,
            'refund_id' => 'REF-' . Str::upper(Str::random(10)),
            'amount' => $refundAmount,
            'reason' => $request->reason,
            'status' => 'pending',
            'refund_type' => $request->refund_type
        ]);

        // Process refund through gateway
        $gatewayResponse = $this->processGatewayRefund($transaction, $refund);

        if ($gatewayResponse['success']) {
            $refund->update([
                'status' => 'completed',
                'gateway_response' => $gatewayResponse['data']
            ]);

            $order->update(['status' => 'refunded']);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $refund
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Refund failed',
            'error' => $gatewayResponse['message']
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Get payment history
     */
    public function paymentHistory(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::with(['order', 'paymentMethod'])
            ->whereHas('order', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string'
        ]);

        $transaction = Transaction::with(['order', 'paymentMethod'])
            ->where('transaction_id', $request->transaction_id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verify with gateway
        $verification = $this->verifyGatewayPayment($transaction);

        if ($verification['success']) {
            $transaction->update([
                'status' => $verification['data']['status'],
                'gateway_response' => $verification['data']
            ]);

            if ($verification['data']['status'] === 'completed') {
                $transaction->order->update(['status' => 'processing']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Get refund details
     */
    public function refundDetails($refundId)
    {
        $refund = Refund::with(['transaction.order', 'transaction.paymentMethod'])
            ->where('refund_id', $refundId)
            ->first();

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $refund
        ]);
    }

    /**
     * Get user's refunds
     */
    public function refunds(Request $request)
    {
        $user = $request->user();

        $refunds = Refund::with(['transaction.order', 'transaction.paymentMethod'])
            ->whereHas('transaction.order', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $refunds
        ]);
    }

    /**
     * Initialize gateway payment
     */
    private function initializeGatewayPayment($order, $transaction, $gateway)
    {
        $amount = $order->total_amount * 100; // Convert to cents

        switch ($gateway) {
            case 'stripe':
                return $this->initializeStripePayment($order, $transaction, $amount);
            case 'paystack':
                return $this->initializePaystackPayment($order, $transaction, $amount);
            case 'flutterwave':
                return $this->initializeFlutterwavePayment($order, $transaction, $amount);
            default:
                return ['error' => 'Unsupported gateway'];
        }
    }

    /**
     * Initialize Stripe payment
     */
    private function initializeStripePayment($order, $transaction, $amount)
    {
        return [
            'client_secret' => 'sk_test_example',
            'publishable_key' => 'pk_test_example',
            'amount' => $amount
        ];
    }

    /**
     * Initialize Paystack payment
     */
    private function initializePaystackPayment($order, $transaction, $amount)
    {
        return [
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/example',
                'access_code' => 'example_code',
                'reference' => $transaction->transaction_id
            ]
        ];
    }

    /**
     * Initialize Flutterwave payment
     */
    private function initializeFlutterwavePayment($order, $transaction, $amount)
    {
        return [
            'status' => 'success',
            'message' => 'Payment link created',
            'data' => [
                'link' => 'https://checkout.flutterwave.com/example'
            ]
        ];
    }

    /**
     * Handle Stripe webhook
     */
    private function handleStripeWebhook($payload)
    {
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Paystack webhook
     */
    private function handlePaystackWebhook($payload)
    {
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave webhook
     */
    private function handleFlutterwaveWebhook($payload)
    {
        return response()->json(['status' => 'success']);
    }

    /**
     * Process gateway refund
     */
    private function processGatewayRefund($transaction, $refund)
    {
        return ['success' => true, 'data' => ['refund_id' => 'example_refund']];
    }

    /**
     * Verify gateway payment
     */
    private function verifyGatewayPayment($transaction)
    {
        return [
            'success' => true,
            'data' => [
                'status' => 'completed',
                'gateway_transaction_id' => 'example_id'
            ]
        ];
    }

    /**
     * Get payment method ID
     */
    private function getPaymentMethodId($methodName)
    {
        $method = PaymentMethod::where('name', $methodName)->first();
        return $method ? $method->id : null;
    }
}
