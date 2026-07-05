<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        // Menggunakan properti year dan month dari Carbon agar kompatibel dengan semua Database
        $thisYear  = now()->year;
        $thisMonth = now()->month;

        $lastYear  = now()->subMonth()->year;
        $lastMonth = now()->subMonth()->month;

        // Total pendapatan hari ini
        $todaySales = SaleTransaction::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Pendapatan bulan ini menggunakan whereYear dan whereMonth
        $monthSales = SaleTransaction::whereYear('created_at', $thisYear)
            ->whereMonth('created_at', $thisMonth)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Pendapatan bulan lalu
        $lastMonSales = SaleTransaction::whereYear('created_at', $lastYear)
            ->whereMonth('created_at', $lastMonth)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $growthPct = $lastMonSales > 0 ? round((($monthSales - $lastMonSales) / $lastMonSales) * 100, 1) : 0;

        return response()->json([
            'status' => true,
            'data'   => [
                'total_products'    => Product::count(),
                'total_categories'  => Category::count(),
                'total_suppliers'   => Supplier::count(),
                'total_users'       => User::count(),
                'low_stock_count'   => Product::lowStock()->count(),
                'today_sales'       => $todaySales,
                'today_transactions' => SaleTransaction::whereDate('created_at', $today)->count(),
                'month_sales'       => $monthSales,
                'last_month_sales'  => $lastMonSales,
                'growth_percent'    => $growthPct,
                'pending_purchases' => PurchaseTransaction::whereIn('status', ['draft', 'ordered'])->count(),
            ],
        ]);
    }

    public function salesChart(Request $request): JsonResponse
    {
        $type = $request->type ?? 'daily'; // daily | monthly

        // Cek driver database yang sedang digunakan (mysql / pgsql)
        $driver = DB::connection()->getDriverName();

        if ($type === 'monthly') {
            // Sesuaikan format tanggal berdasarkan driver database
            $monthFormat = $driver === 'pgsql' ? "TO_CHAR(created_at, 'YYYY-MM')" : "DATE_FORMAT(created_at, '%Y-%m')";

            $data = SaleTransaction::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupByRaw($monthFormat)
                ->selectRaw("{$monthFormat} as month, SUM(total_amount) as total, COUNT(*) as count")
                ->orderBy('month')
                ->get();
        } else {
            // Sesuaikan format tanggal harian
            $dateFormat = $driver === 'pgsql' ? "TO_CHAR(created_at, 'YYYY-MM-DD')" : "DATE(created_at)";

            $data = SaleTransaction::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays(29))
                ->groupByRaw($dateFormat)
                ->selectRaw("{$dateFormat} as date, SUM(total_amount) as total, COUNT(*) as count")
                ->orderBy('date')
                ->get();
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->limit ?? 10;
        $products = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sale_transactions', 'sale_transactions.id', '=', 'sale_items.sale_transaction_id')
            ->where('sale_transactions.payment_status', 'paid')
            ->when($request->start_date, fn($q, $d) => $q->whereDate('sale_transactions.created_at', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('sale_transactions.created_at', '<=', $d))
            ->groupBy('products.id', 'products.name', 'products.code')
            ->selectRaw('products.id, products.name, products.code, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_revenue')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return response()->json(['status' => true, 'data' => $products]);
    }

    public function recentTransactions(): JsonResponse
    {
        $transactions = SaleTransaction::with('user:id,name')
            ->latest()->limit(10)
            ->get(['id', 'code', 'user_id', 'customer_name', 'total_amount', 'payment_method', 'payment_status', 'created_at']);

        return response()->json(['status' => true, 'data' => $transactions]);
    }
}
