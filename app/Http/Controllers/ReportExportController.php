<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use Carbon\Carbon;

class ReportExportController extends Controller
{
    /**
     * Export PDF: /api/reports/export-pdf
     */
    public function exportPdf(Request $request): Response
    {
        $period = $request->get('period', 'month');
        $dateRange = $request->get('date_range'); // optional, format: start_date,end_date

        if ($dateRange) {
            [$startDate, $endDate] = explode(',', $dateRange);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
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
        }

        $orders = Order::with('items.product', 'user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();

        $data = [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'orders' => $orders,
        ];

        $pdf = Pdf::loadView('reports.revenue', $data);

        $filename = 'revenue_report_' . $period . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Excel: /api/reports/export-excel
     */
    public function exportExcel(Request $request)
    {
        $period = $request->get('period', 'month');
        $dateRange = $request->get('date_range'); // optional, format: start_date,end_date

        if ($dateRange) {
            [$startDate, $endDate] = explode(',', $dateRange);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
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
        }

        $orders = Order::with('items.product', 'user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'revenue_report_' . $period . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.xlsx';

        return Excel::download(new OrdersExport($orders), $filename);
    }
}