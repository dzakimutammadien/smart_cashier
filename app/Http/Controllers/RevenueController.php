<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueController extends Controller
{
    /**
     * Get revenue statistics
     */
    public function revenueStatistics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $now = Carbon::now();

        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
        }

        // Query transactions based on period
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();

        // Calculate total_revenue from sum(total_amount)
        $totalRevenue = $orders->sum('total');

        // Calculate total_orders from count(transactions)
        $totalOrders = $orders->count();

        // Calculate percentage_change vs previous period
        $periodLength = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $startDate->copy()->subDays($periodLength);
        $previousEndDate = $endDate->copy()->subDays($periodLength);

        $previousOrders = Order::whereBetween('created_at', [$previousStartDate, $previousEndDate])->get();
        $previousRevenue = $previousOrders->sum('total');

        $percentageChange = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'percentage_change' => round($percentageChange, 2),
            ],
        ]);
    }
}