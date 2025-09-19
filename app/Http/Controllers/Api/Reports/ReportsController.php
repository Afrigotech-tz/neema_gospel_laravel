<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reports/orders",
     *     tags={"Reports"},
     *     summary="Generate orders report PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         @OA\Schema(type="string", enum={"pending","processing","shipped","delivered","cancelled","refunded"})
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF report generated",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    // public function ordersReport(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled,refunded',
    //         'start_date' => 'nullable|date|before_or_equal:end_date',
    //         'end_date' => 'nullable|date|after_or_equal:start_date',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation errors',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }
    //     // Build query
    //     $query = Order::with(['user', 'address', 'items.product', 'paymentMethod']);
    //     // Filter by status if provided
    //     if ($request->has('status') && $request->status) {
    //         $query->where('status', $request->status);
    //     }
    //     // Filter by date range if provided
    //     if ($request->has('start_date') && $request->start_date) {
    //         $query->whereDate('created_at', '>=', $request->start_date);
    //     }
    //     if ($request->has('end_date') && $request->end_date) {
    //         $query->whereDate('created_at', '<=', $request->end_date);
    //     }
    //     // Order by creation date
    //     $orders = $query->orderBy('created_at', 'desc')->get();
    //     // Calculate summary statistics
    //     $summary = [
    //         'total_orders' => $orders->count(),
    //         'total_amount' => $orders->sum('total_amount'),
    //         'status_breakdown' => $orders->groupBy('status')->map->count(),
    //         'date_range' => [
    //             'start' => $request->start_date ?? null,
    //             'end' => $request->end_date ?? null,
    //         ],
    //         'status_filter' => $request->status ?? 'All',
    //     ];
    //     // Generate PDF
    //     $pdf = Pdf::loadView('reports.orders', [
    //         'orders' => $orders,
    //         'summary' => $summary,
    //         'generated_at' => now(),
    //     ]);
    //     // Set paper size and orientation
    //     $pdf->setPaper('a4', 'landscape');
    //     // Return PDF as download
    //     return $pdf->stream('orders_report_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    // }
    public function ordersReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled,refunded',
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query
        $query = Order::with(['user', 'address', 'items.product', 'paymentMethod']);

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Order by creation date
        $orders = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('total_amount'),
            'status_breakdown' => $orders->groupBy('status')->map->count(),
            'date_range' => [
                'start' => $request->start_date ?? null,
                'end' => $request->end_date ?? null,
            ],
            'status_filter' => $request->status ?? 'All',
        ];

        // Generate the static logo path
        $logoPath = public_path('assets/logo/logo.png');  // absolute path for dompdf
        // Or for URL access: $logoUrl = asset('assets/logo/logo.png');

        // Generate PDF
        $pdf = Pdf::loadView('reports.orders', [
            'orders' => $orders,
            'summary' => $summary,
            'generated_at' => now(),
            'logoPath' => $logoPath, 
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        // Return PDF as download
        return $pdf->stream('orders_report_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }


    
    /**
     * @OA\Get(
     *     path="/api/reports/orders/status-summary",
     *     tags={"Reports"},
     *     summary="Get orders status summary",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders status summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function ordersStatusSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query
        $query = Order::query();

        // Filter by date range if provided
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Get status breakdown
        $statusBreakdown = $query
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Get total statistics
        $totalStats = $query
            ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_amount')
            ->first();

        $data = [
            'total_orders' => $totalStats->total_orders ?? 0,
            'total_amount' => $totalStats->total_amount ?? 0,
            'status_breakdown' => [
                'pending' => [
                    'count' => $statusBreakdown['pending']->count ?? 0,
                    'total_amount' => $statusBreakdown['pending']->total_amount ?? 0,
                ],
                'processing' => [
                    'count' => $statusBreakdown['processing']->count ?? 0,
                    'total_amount' => $statusBreakdown['processing']->total_amount ?? 0,
                ],
                'shipped' => [
                    'count' => $statusBreakdown['shipped']->count ?? 0,
                    'total_amount' => $statusBreakdown['shipped']->total_amount ?? 0,
                ],
                'delivered' => [
                    'count' => $statusBreakdown['delivered']->count ?? 0,
                    'total_amount' => $statusBreakdown['delivered']->total_amount ?? 0,
                ],
                'cancelled' => [
                    'count' => $statusBreakdown['cancelled']->count ?? 0,
                    'total_amount' => $statusBreakdown['cancelled']->total_amount ?? 0,
                ],
                'refunded' => [
                    'count' => $statusBreakdown['refunded']->count ?? 0,
                    'total_amount' => $statusBreakdown['refunded']->total_amount ?? 0,
                ],
            ],
            'date_range' => [
                'start' => $request->start_date ?? null,
                'end' => $request->end_date ?? null,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
