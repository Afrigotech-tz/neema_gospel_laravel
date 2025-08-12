<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected $apiKey;
    protected $baseUrl;
    protected $merchantId;

    public function __construct()
    {
        $this->apiKey = config('services.payment_gateway.api_key');
        $this->baseUrl = config('services.payment_gateway.base_url');
        $this->merchantId = config('services.payment_gateway.merchant_id');
    }

    /**
     * Process a refund through the payment gateway
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function processRefund(string $transactionId, float $amount, string $reason = ''): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/refunds', [
                'transaction_id' => $transactionId,
                'amount' => $amount * 100, // Convert to cents
                'reason' => $reason,
                'merchant_id' => $this->merchantId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'refund_id' => $data['refund_id'] ?? null,
                    'response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Refund request failed',
            ];

        } catch (\Exception $e) {
            Log::error('Payment gateway refund error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get refund status from payment gateway
     *
     * @param string $refundId
     * @return array
     */
    public function getRefundStatus(string $refundId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/refunds/' . $refundId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Unable to fetch refund status',
            ];

        } catch (\Exception $e) {
            Log::error('Payment gateway refund status error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a partial refund
     *
     * @param Transaction $transaction
     * @param float $amount
     * @param array $items
     * @param string $reason
     * @return array
     */
    public function processPartialRefund(Transaction $transaction, float $amount, array $items = [], string $reason = ''): array
    {
        return $this->processRefund(
            $transaction->gateway_transaction_id,
            $amount,
            $reason
        );
    }
}
