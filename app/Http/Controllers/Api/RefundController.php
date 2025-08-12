<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    protected $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Process a refund for an order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processRefund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
            'items' => 'array',
            'items.*.order_item_id' => 'required_with:items|exists:order_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.amount' => 'required_with:items|numeric|min:0.01',
        ]);

        $order = Order::with(['items', 'transactions'])->findOrFail($request->order_id);

        // Check if order has a successful transaction
        $transaction = $order->transactions()->where('status', 'completed')->first();
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No successful transaction found for this order',
            ], 422);
        }

        // Validate refund amount doesn't exceed transaction amount
        $maxRefundAmount = $transaction->amount;
        if ($request->amount > $maxRefundAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed transaction amount',
            ], 422);
        }

        // Check for existing refunds
        $existingRefunds = $order->refunds()->where('status', '!=', 'failed')->sum('amount');
        if (($existingRefunds + $request->amount) > $maxRefundAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Total refund amount would exceed transaction amount',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create refund record
            $refund = Refund::create([
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'amount' => $request->amount,
                'reason' => $request->reason,
                'status' => 'pending',
                'processed_by' => auth()->id(),
            ]);

            // Process refund through payment gateway
            $gatewayResponse = $this->paymentGatewayService->processRefund(
                $transaction->gateway_transaction_id,
                $request->amount,
                $request->reason
            );

            if ($gatewayResponse['success']) {
                $refund->update([
                    'status' => 'completed',
                    'gateway_refund_id' => $gatewayResponse['refund_id'],
                    'gateway_response' => $gatewayResponse['response'],
                ]);

                // Create refund items if provided
                if ($request->has('items')) {
                    foreach ($request->items as $item) {
                        RefundItem::create([
                            'refund_id' => $refund->id,
                            'order_item_id' => $item['order_item_id'],
                            'quantity' => $item['quantity'],
                            'amount' => $item['amount'],
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund' => $refund->load('items'),
                ]);
            } else {
                $refund->update([
                    'status' => 'failed',
                    'gateway_response' => $gatewayResponse['error'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Refund failed: ' . $gatewayResponse['error'],
                    'refund' => $refund,
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund processing error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the refund',
            ], 500);
        }
    }

    /**
     * Get refund details
     *
     * @param string $refundId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($refundId)
    {
        $refund = Refund::with(['order', 'items', 'transaction'])
            ->where('id', $refundId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'refund' => $refund,
        ]);
    }

    /**
     * Get refund status from payment gateway
     *
     * @param string $refundId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus($refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if (!$refund->gateway_refund_id) {
            return response()->json([
                'success' => false,
                'message' => 'No gateway refund ID found',
            ], 422);
        }

        $statusResponse = $this->paymentGatewayService->getRefundStatus(
            $refund->gateway_refund_id
        );

        if ($statusResponse['success']) {
            // Update refund status based on gateway response
            $gatewayStatus = $statusResponse['data']['status'] ?? 'unknown';
            $newStatus = $this->mapGatewayStatus($gatewayStatus);

            if ($newStatus !== $refund->status) {
                $refund->update(['status' => $newStatus]);
            }

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'gateway_data' => $statusResponse['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $statusResponse['error'],
        ], 422);
    }

    /**
     * Get refunds for an order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderRefunds(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $refunds = Refund::with(['items', 'transaction'])
            ->where('order_id', $request->order_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'refunds' => $refunds,
        ]);
    }

    /**
     * Map gateway status to local status
     *
     * @param string $gatewayStatus
     * @return string
     */
    private function mapGatewayStatus($gatewayStatus)
    {
        $statusMap = [
            'pending' => 'pending',
            'processing' => 'processing',
            'succeeded' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[strtolower($gatewayStatus)] ?? 'pending';
    }
}
