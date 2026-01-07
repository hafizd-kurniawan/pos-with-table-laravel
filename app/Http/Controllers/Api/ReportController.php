<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyReportExport;
use App\Exports\PeriodReportExport;

class ReportController extends Controller
{
    protected $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    /**
     * Get tenant ID from authenticated user or request
     */
    private function getTenantId(Request $request)
    {
        // From authenticated user
        if (auth()->check()) {
            $userId = auth()->id();
            $user = \DB::table('users')->where('id', $userId)->first();
            if ($user && $user->tenant_id) {
                return $user->tenant_id;
            }
        }
        
        // From request header
        if ($request->header('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }
        
        // From request parameter
        if ($request->input('tenant_id')) {
            return $request->input('tenant_id');
        }
        
        return null;
    }
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = Order::query()
            ->whereBetween('created_at', [$start_date, $end_date]);


        $orders = $query->get();

        $totalRevenue = $orders->sum('total_amount');

        $totalSoldQuantity = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start_date, $end_date])
            ->sum('order_items.quantity');
        $data = [
            'total_revenue' => $totalRevenue,
            'total_sold_quantity' => $totalSoldQuantity
        ];

        // Mengirim respon
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function productSales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            'products.price as product_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(order_items.created_at)'), [$start_date, $end_date])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();
        return response()->json([
            'status' => 'success',
            'data' => $totalProductSold
        ]);
    }
    
    /**
     * Get daily summary report (NEW - Enhanced)
     * GET /api/reports/daily-summary?date=2025-11-13
     */
    public function dailySummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $summary = $this->reportService->getDailySummary($tenantId, $request->date);
            
            // Get top products for the day
            $topProducts = $this->reportService->getTopProducts(
                $tenantId, 
                $request->date, 
                $request->date, 
                5
            );
            
            return response()->json([
                'success' => true,
                'data' => array_merge($summary, [
                    'top_products' => $topProducts,
                ]),
                'meta' => [
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Daily summary error', [
                'tenant_id' => $tenantId,
                'date' => $request->date,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate daily summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get period summary report (NEW - Enhanced)
     * GET /api/reports/period-summary?start_date=2025-11-01&end_date=2025-11-30
     */
    public function periodSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $summary = $this->reportService->getPeriodSummary(
                $tenantId, 
                $request->start_date, 
                $request->end_date
            );
            
            // Get top products for the period
            $topProducts = $this->reportService->getTopProducts(
                $tenantId, 
                $request->start_date, 
                $request->end_date, 
                10
            );
            
            $summary['top_products'] = $topProducts;
            
            return response()->json([
                'success' => true,
                'data' => $summary,
                'meta' => [
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Period summary error', [
                'tenant_id' => $tenantId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate period summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get top selling products
     * GET /api/reports/top-products?start_date=2025-11-01&end_date=2025-11-30&limit=10
     */
    public function topProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        $limit = $request->input('limit', 10);
        
        try {
            $topProducts = $this->reportService->getTopProducts(
                $tenantId, 
                $request->start_date, 
                $request->end_date, 
                $limit
            );
            
            return response()->json([
                'success' => true,
                'data' => $topProducts,
                'meta' => [
                    'period' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                    'limit' => $limit,
                    'total' => count($topProducts),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Top products error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get top products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate/refresh daily summary cache
     * POST /api/reports/generate-daily-summary
     */
    public function generateDailySummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'force' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        $force = $request->input('force', false);
        
        try {
            $summary = $this->reportService->generateDailySummary(
                $tenantId, 
                $request->date, 
                $force
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Daily summary generated successfully',
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Generate daily summary error', [
                'tenant_id' => $tenantId,
                'date' => $request->date,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate daily summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get sales trend for charts
     * GET /api/reports/sales-trend?start_date=2025-11-01&end_date=2025-11-30&group_by=daily
     */
    public function salesTrend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'group_by' => 'nullable|in:hourly,daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        $groupBy = $request->input('group_by', 'daily');
        
        try {
            $trend = $this->reportService->getSalesTrend(
                $tenantId, 
                $request->start_date, 
                $request->end_date,
                $groupBy
            );
            
            return response()->json([
                'success' => true,
                'data' => $trend,
                'meta' => [
                    'period' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                    'group_by' => $groupBy,
                    'total_periods' => count($trend),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Sales trend error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sales trend',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get category performance
     * GET /api/reports/category-performance?start_date=2025-11-01&end_date=2025-11-30
     */
    public function categoryPerformance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $categories = $this->reportService->getCategoryPerformance(
                $tenantId, 
                $request->start_date, 
                $request->end_date
            );
            
            return response()->json([
                'success' => true,
                'data' => $categories,
                'meta' => [
                    'period' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                    'total_categories' => count($categories),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Category performance error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get hourly breakdown
     * GET /api/reports/hourly-breakdown?start_date=2025-11-01&end_date=2025-11-30
     */
    public function hourlyBreakdown(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $hourly = $this->reportService->getHourlyBreakdown(
                $tenantId, 
                $request->start_date, 
                $request->end_date
            );
            
            // Find peak hours
            $peakHour = collect($hourly)->sortByDesc('total_sales')->first();
            
            return response()->json([
                'success' => true,
                'data' => $hourly,
                'meta' => [
                    'period' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                    'peak_hour' => $peakHour ? [
                        'hour' => $peakHour['hour'],
                        'label' => $peakHour['label'],
                        'total_sales' => $peakHour['total_sales'],
                    ] : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Hourly breakdown error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get hourly breakdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get payment trends
     * GET /api/reports/payment-trends?start_date=2025-11-01&end_date=2025-11-30&group_by=daily
     */
    public function paymentTrends(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'group_by' => 'nullable|in:hourly,daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        $groupBy = $request->input('group_by', 'daily');
        
        try {
            $trends = $this->reportService->getPaymentTrends(
                $tenantId, 
                $request->start_date, 
                $request->end_date,
                $groupBy
            );
            
            return response()->json([
                'success' => true,
                'data' => $trends,
                'meta' => [
                    'period' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                    'group_by' => $groupBy,
                    'total_periods' => count($trends),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Payment trends error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export daily report to PDF
     * GET /api/reports/export/daily-pdf?date=2025-11-13
     */
    public function exportDailyPDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $summary = $this->reportService->getDailySummary($tenantId, $request->date);
            $topProducts = $this->reportService->getTopProducts($tenantId, $request->date, $request->date, 10);
            
            $data = [
                'date' => $request->date,
                'summary' => $summary['summary'],
                'paymentBreakdown' => $summary['payment_breakdown'],
                'topProducts' => $topProducts,
            ];
            
            $pdf = Pdf::loadView('reports.daily-pdf', $data);
            $pdf->setPaper('a4', 'portrait');
            
            $filename = 'laporan-harian-' . $request->date . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Export daily PDF error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export daily report to Excel
     * GET /api/reports/export/daily-excel?date=2025-11-13
     */
    public function exportDailyExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $summary = $this->reportService->getDailySummary($tenantId, $request->date);
            $topProducts = $this->reportService->getTopProducts($tenantId, $request->date, $request->date, 10);
            
            $data = [
                'date' => $request->date,
                'summary' => $summary['summary'],
                'payment_breakdown' => $summary['payment_breakdown'],
                'top_products' => $topProducts,
            ];
            
            $filename = 'laporan-harian-' . $request->date . '.xlsx';
            
            return Excel::download(new DailyReportExport($data, $request->date), $filename);
        } catch (\Exception $e) {
            Log::error('Export daily Excel error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export period report to PDF
     * GET /api/reports/export/period-pdf?start_date=2025-11-01&end_date=2025-11-30
     */
    public function exportPeriodPDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $data = $this->reportService->getPeriodSummary($tenantId, $request->start_date, $request->end_date);
            $topProducts = $this->reportService->getTopProducts($tenantId, $request->start_date, $request->end_date, 10);
            
            $data['top_products'] = $topProducts;
            
            $viewData = [
                'startDate' => $request->start_date,
                'endDate' => $request->end_date,
                'data' => $data,
            ];
            
            $pdf = Pdf::loadView('reports.period-pdf', $viewData);
            $pdf->setPaper('a4', 'portrait');
            
            $filename = 'laporan-periode-' . $request->start_date . '-to-' . $request->end_date . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Export period PDF error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export period report to Excel
     * GET /api/reports/export/period-excel?start_date=2025-11-01&end_date=2025-11-30
     */
    public function exportPeriodExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $tenantId = $this->getTenantId($request);
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        try {
            $data = $this->reportService->getPeriodSummary($tenantId, $request->start_date, $request->end_date);
            $topProducts = $this->reportService->getTopProducts($tenantId, $request->start_date, $request->end_date, 10);
            
            $data['top_products'] = $topProducts;
            
            $filename = 'laporan-periode-' . $request->start_date . '-to-' . $request->end_date . '.xlsx';
            
            return Excel::download(
                new PeriodReportExport($data, $request->start_date, $request->end_date), 
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Export period Excel error', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
