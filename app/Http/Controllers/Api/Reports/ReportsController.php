<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
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
        $logoPath = public_path('assets/logo/logo.png');  
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
        return $pdf->stream('ORDERS_REPORT_' . now()->format('Y-m-d_H-i-s') . '.pdf');
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

    /**
     * @OA\Get(
     *     path="/api/reports/users",
     *     tags={"Reports"},
     *     summary="Generate users report PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status",
     *         @OA\Schema(type="string", enum={"active","inactive","suspended"})
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
    public function usersReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,inactive,suspended',
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
        $query = User::query();

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
        $users = $query->with('roles')->orderBy('created_at', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_users' => $users->count(),
            'date_range' => [
                'start' => $request->start_date ?? null,
                'end' => $request->end_date ?? null,
            ],
            'status_filter' => $request->status ?? 'All',
        ];

        // Generate the static logo path
        $logoPath = public_path('assets/logo/logo.png');  // absolute path for dompdf

        // Generate PDF
        $pdf = Pdf::loadView('reports.users', [
            'users' => $users,
            'summary' => $summary,
            'generated_at' => now(),
            'logoPath' => $logoPath,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        // Return PDF as download
        return $pdf->stream('USERS_REPORT_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * @OA\Get(
     *     path="/api/reports/products",
     *     tags={"Reports"},
     *     summary="Generate products report PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         @OA\Schema(type="boolean")
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
    public function productsReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query
        $query = Product::with(['category', 'variants']);

        // Filter by category if provided
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Order by creation date
        $products = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_products' => $products->count(),
            'active_products' => $products->where('is_active', true)->count(),
            'inactive_products' => $products->where('is_active', false)->count(),
            'total_stock' => $products->sum('stock_quantity'),
            'category_filter' => $request->category_id ? 'Category ID: ' . $request->category_id : 'All Categories',
            'status_filter' => $request->has('is_active') ? ($request->boolean('is_active') ? 'Active Only' : 'Inactive Only') : 'All Statuses',
        ];

        // Generate the static logo path
        $logoPath = public_path('assets/logo/logo.png');  // absolute path for dompdf

        // Generate PDF
        $pdf = Pdf::loadView('reports.products', [
            'products' => $products,
            'summary' => $summary,
            'generated_at' => now(),
            'logoPath' => $logoPath,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        // Return PDF as download
        return $pdf->stream('PRODUCTS_REPORT_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * @OA\Get(
     *     path="/api/reports/stock",
     *     tags={"Reports"},
     *     summary="Generate stock report PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="low_stock_only",
     *         in="query",
     *         description="Show only low stock items (stock <= 10)",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="out_of_stock_only",
     *         in="query",
     *         description="Show only out of stock items (stock = 0)",
     *         @OA\Schema(type="boolean")
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
    public function stockReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'low_stock_only' => 'nullable|boolean',
            'out_of_stock_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Build query for products with stock information
        $query = Product::with(['category', 'variants']);

        // Filter by stock levels if requested
        if ($request->boolean('low_stock_only')) {
            $query->where('stock_quantity', '<=', 10)->where('stock_quantity', '>', 0);
        } elseif ($request->boolean('out_of_stock_only')) {
            $query->where('stock_quantity', '=', 0);
        }

        // Order by stock quantity (lowest first)
        $products = $query->orderBy('stock_quantity', 'asc')->get();

        // Get stock summary for variants as well
        $variants = ProductVariant::with(['product', 'product.category'])
            ->when($request->boolean('low_stock_only'), function ($q) {
                return $q->where('stock', '<=', 10)->where('stock', '>', 0);
            })
            ->when($request->boolean('out_of_stock_only'), function ($q) {
                return $q->where('stock', '=', 0);
            })
            ->orderBy('stock', 'asc')
            ->get();

        // Calculate summary statistics
        $summary = [
            'total_products' => $products->count(),
            'total_variants' => $variants->count(),
            'low_stock_products' => $products->where('stock_quantity', '<=', 10)->where('stock_quantity', '>', 0)->count(),
            'out_of_stock_products' => $products->where('stock_quantity', 0)->count(),
            'low_stock_variants' => $variants->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'out_of_stock_variants' => $variants->where('stock', 0)->count(),
            'total_product_stock' => $products->sum('stock_quantity'),
            'total_variant_stock' => $variants->sum('stock'),
            'filter_type' => $request->boolean('low_stock_only') ? 'Low Stock Only' :
                           ($request->boolean('out_of_stock_only') ? 'Out of Stock Only' : 'All Stock Levels'),
        ];

        // Generate the static logo path
        $logoPath = public_path('assets/logo/logo.png');  // absolute path for dompdf

        // Generate PDF
        $pdf = Pdf::loadView('reports.stock', [
            'products' => $products,
            'variants' => $variants,
            'summary' => $summary,
            'generated_at' => now(),
            'logoPath' => $logoPath,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        // Return PDF as download
        return $pdf->stream('STOCK_REPORT' . now()->format('Y-m-d_H-i-s') . '.pdf');

    }


}
