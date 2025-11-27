<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get daily sales report
     */
    public function dailySales(Request $request): JsonResponse
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $sales = Order::whereDate('created_at', $date)
            ->with('items.product')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalOrders = $sales->count();
        $totalItems = $sales->sum(function ($order) {
            return $order->items->sum('quantity');
        });
        $averageTransactionValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $productSales = OrderItem::whereHas('order', function ($query) use ($date) {
            $query->whereDate('created_at', $date);
        })
        ->with('product')
        ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price * quantity) as total_revenue'))
        ->groupBy('product_id')
        ->orderBy('total_revenue', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_items' => $totalItems,
                'average_transaction_value' => round($averageTransactionValue, 2),
                'product_sales' => $productSales->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'total_quantity' => $item->total_quantity,
                        'total_revenue' => $item->total_revenue,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get weekly sales report
     */
    public function weeklySales(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->toDateString());

        $sales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with('items.product')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalOrders = $sales->count();
        $totalItems = $sales->sum(function ($order) {
            return $order->items->sum('quantity');
        });
        $averageTransactionValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $dailyBreakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $productSales = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->with('product')
        ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price * quantity) as total_revenue'))
        ->groupBy('product_id')
        ->orderBy('total_revenue', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_items' => $totalItems,
                'average_transaction_value' => round($averageTransactionValue, 2),
                'daily_breakdown' => $dailyBreakdown,
                'product_sales' => $productSales->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'total_quantity' => $item->total_quantity,
                        'total_revenue' => $item->total_revenue,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get monthly sales report
     */
    public function monthlySales(Request $request): JsonResponse
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $sales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with('items.product')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalOrders = $sales->count();
        $totalItems = $sales->sum(function ($order) {
            return $order->items->sum('quantity');
        });
        $averageTransactionValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $weeklyBreakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('WEEK(created_at) as week'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('year', 'week')
            ->orderBy('year')
            ->orderBy('week')
            ->get();

        $productSales = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->with('product')
        ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price * quantity) as total_revenue'))
        ->groupBy('product_id')
        ->orderBy('total_revenue', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_items' => $totalItems,
                'average_transaction_value' => round($averageTransactionValue, 2),
                'weekly_breakdown' => $weeklyBreakdown,
                'product_sales' => $productSales->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'total_quantity' => $item->total_quantity,
                        'total_revenue' => $item->total_revenue,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get product performance analytics
     */
    public function productAnalytics(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);

        $productPerformance = OrderItem::with('product')
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(price * quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_id) as order_count'),
                DB::raw('AVG(price) as avg_price')
            )
            ->groupBy('product_id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        $topProducts = $productPerformance->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'total_sold' => $item->total_sold,
                'total_revenue' => $item->total_revenue,
                'order_count' => $item->order_count,
                'avg_price' => $item->avg_price,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'top_products' => $topProducts,
            ],
        ]);
    }

    /**
     * Get revenue statistics
     */
    public function revenueStatistics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $now = Carbon::now();

        switch ($period) {
            case 'day':
                $startDate = $now->startOfDay();
                $endDate = $now->endOfDay();
                $groupBy = DB::raw('HOUR(created_at)');
                $format = '%H';
                break;
            case 'week':
                $startDate = $now->startOfWeek();
                $endDate = $now->endOfWeek();
                $groupBy = DB::raw('DATE(created_at)');
                $format = '%Y-%m-%d';
                break;
            case 'month':
                $startDate = $now->startOfMonth();
                $endDate = $now->endOfMonth();
                $groupBy = DB::raw('DATE(created_at)');
                $format = '%Y-%m-%d';
                break;
            case 'year':
                $startDate = $now->startOfYear();
                $endDate = $now->endOfYear();
                $groupBy = DB::raw('MONTH(created_at)');
                $format = '%m';
                break;
            default:
                $startDate = $now->startOfMonth();
                $endDate = $now->endOfMonth();
                $groupBy = DB::raw('DATE(created_at)');
                $format = '%Y-%m-%d';
        }

        $revenueData = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select($groupBy, DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy($groupBy)
            ->orderBy($groupBy)
            ->get();

        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        // Compare with previous period
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
        $previousPeriodEnd = $startDate->copy()->subDay();

        $previousRevenue = Order::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->sum('total');
        $previousOrders = Order::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();

        $revenueChange = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $ordersChange = $previousOrders > 0 ? (($totalOrders - $previousOrders) / $previousOrders) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'revenue_change_percent' => round($revenueChange, 2),
                'orders_change_percent' => round($ordersChange, 2),
                'chart_data' => $revenueData,
            ],
        ]);
    }

    /**
     * Get dashboard overview
     */
    public function dashboard(Request $request): JsonResponse
    {
        $lowStockThreshold = $request->get('low_stock_threshold', 10);

        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's metrics
        $todayRevenue = Order::whereDate('created_at', $today)->sum('total');
        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayAvgTransaction = $todayOrders > 0 ? $todayRevenue / $todayOrders : 0;

        // This week's metrics
        $weekRevenue = Order::whereBetween('created_at', [$thisWeek, Carbon::now()])->sum('total');
        $weekOrders = Order::whereBetween('created_at', [$thisWeek, Carbon::now()])->count();
        $weekAvgTransaction = $weekOrders > 0 ? $weekRevenue / $weekOrders : 0;

        // This month's metrics
        $monthRevenue = Order::whereBetween('created_at', [$thisMonth, Carbon::now()])->sum('total');
        $monthOrders = Order::whereBetween('created_at', [$thisMonth, Carbon::now()])->count();
        $monthAvgTransaction = $monthOrders > 0 ? $monthRevenue / $monthOrders : 0;

        // Total products
        $totalProducts = Product::count();

        // Low stock products
        $lowStockProducts = Product::where('stock', '<', $lowStockThreshold)->get();
        $lowStockCount = $lowStockProducts->count();

        // Top selling products (last 30 days)
        $topProducts = OrderItem::whereHas('order', function ($query) {
            $query->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()]);
        })
        ->with('product')
        ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
        ->groupBy('product_id')
        ->orderBy('total_sold', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            return [
                'product_name' => $item->product->name,
                'total_sold' => $item->total_sold,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'revenue' => $todayRevenue,
                    'orders' => $todayOrders,
                    'average_transaction' => round($todayAvgTransaction, 2),
                ],
                'this_week' => [
                    'revenue' => $weekRevenue,
                    'orders' => $weekOrders,
                    'average_transaction' => round($weekAvgTransaction, 2),
                ],
                'this_month' => [
                    'revenue' => $monthRevenue,
                    'orders' => $monthOrders,
                    'average_transaction' => round($monthAvgTransaction, 2),
                ],
                'inventory' => [
                    'total_products' => $totalProducts,
                    'low_stock_products' => $lowStockCount,
                ],
                'low_stock_alerts' => [
                    'threshold' => $lowStockThreshold,
                    'count' => $lowStockCount,
                    'products' => $lowStockProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'stock' => $product->stock,
                        ];
                    }),
                ],
                'top_products' => $topProducts,
            ],
        ]);
    }

    /**
     * Export sales report to PDF
     */
    public function exportSalesReport(Request $request): Response
    {
        $type = $request->get('type', 'daily'); // daily, weekly, monthly
        $date = $request->get('date', Carbon::today()->toDateString());
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $data = [];

        switch ($type) {
            case 'daily':
                $response = $this->dailySales($request);
                $data = json_decode($response->getContent(), true)['data'];
                $data['type'] = 'Daily Sales Report';
                break;
            case 'weekly':
                $response = $this->weeklySales($request);
                $data = json_decode($response->getContent(), true)['data'];
                $data['type'] = 'Weekly Sales Report';
                break;
            case 'monthly':
                $response = $this->monthlySales($request);
                $data = json_decode($response->getContent(), true)['data'];
                $data['type'] = 'Monthly Sales Report';
                break;
        }

        $pdf = Pdf::loadView('reports.sales', compact('data'));

        $filename = 'sales_report_' . $type . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export product analytics to CSV
     */
    public function exportProductAnalytics(Request $request): Response
    {
        $response = $this->productAnalytics($request);
        $data = json_decode($response->getContent(), true)['data'];

        $csvData = [];
        $csvData[] = ['Product Name', 'Total Sold', 'Total Revenue', 'Order Count', 'Average Price'];

        foreach ($data['top_products'] as $product) {
            $csvData[] = [
                $product['product_name'],
                $product['total_sold'],
                $product['total_revenue'],
                $product['order_count'],
                $product['avg_price'],
            ];
        }

        $filename = 'product_analytics_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get low stock products
     */
    public function lowStockProducts(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 10);
        $limit = $request->get('limit', 50);

        $lowStockProducts = Product::with('category')
            ->where('stock', '<', $threshold)
            ->orderBy('stock', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'threshold' => $threshold,
                'products' => $lowStockProducts->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'stock' => $product->stock,
                        'price' => $product->price,
                        'category' => $product->category->name ?? 'N/A',
                    ];
                }),
            ],
        ]);
    }

    /**
     * Export low stock products to CSV
     */
    public function exportLowStockProducts(Request $request): Response
    {
        $threshold = $request->get('threshold', 10);

        $lowStockProducts = Product::with('category')
            ->where('stock', '<', $threshold)
            ->orderBy('stock', 'asc')
            ->get();

        $csvData = [];
        $csvData[] = ['Product ID', 'Name', 'Stock', 'Price', 'Category'];

        foreach ($lowStockProducts as $product) {
            $csvData[] = [
                $product->id,
                $product->name,
                $product->stock,
                $product->price,
                $product->category->name ?? 'N/A',
            ];
        }

        $filename = 'low_stock_products_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export orders data to CSV
     */
    public function exportOrders(Request $request): Response
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $orders = Order::with('items.product', 'user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Order ID', 'Date', 'Customer', 'Total', 'Items Count', 'Status'];

        foreach ($orders as $order) {
            $csvData[] = [
                $order->id,
                $order->created_at->format('Y-m-d H:i:s'),
                $order->user->name ?? 'N/A',
                $order->total,
                $order->items->sum('quantity'),
                'Completed', // Assuming all orders are completed
            ];
        }

        $filename = 'orders_' . $startDate . '_to_' . $endDate . '.csv';

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}