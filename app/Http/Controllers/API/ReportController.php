<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use App\Exports\ProductsExport;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $sales = SaleTransaction::with(['user:id,name', 'items.product:id,name,code'])
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->payment_status, fn($q, $s) => $q->where('payment_status', $s))
            ->when($request->payment_method, fn($q, $m) => $q->where('payment_method', $m))
            ->latest()
            ->paginate($request->per_page ?? 15);

        $summary = SaleTransaction::when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->where('payment_status', 'paid')
            ->selectRaw('COUNT(*) as total_transaksi, SUM(total_amount) as total_pendapatan, SUM(discount) as total_diskon')
            ->first();

        return response()->json(['status' => true, 'data' => $sales->items(), 'summary' => $summary, 'meta' => ['total' => $sales->total(), 'last_page' => $sales->lastPage()]]);
    }

    public function exportSales(Request $request)
    {
        $type  = $request->type ?? 'excel';
        $start = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end   = $request->end_date   ?? now()->toDateString();

        $sales = SaleTransaction::with(['user:id,name', 'items.product:id,name'])
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->where('payment_status', 'paid')
            ->get();

        ActivityLog::record('EXPORT', "Laporan penjualan diekspor ({$type}) periode {$start} s/d {$end}.");

        $filename = 'laporan-penjualan-' . str_replace(['-', ' '], '_', $start) . '_sd_' . str_replace(['-', ' '], '_', $end);

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('exports.sales-pdf', [
                'sales'     => $sales,
                'start'     => $start,
                'end'       => $end,
                'generated' => now()->format('d/m/Y H:i'),
            ])->setPaper('A4', 'landscape');
            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new SalesExport($sales), $filename . '.xlsx');
    }

    public function exportProducts(Request $request)
    {
        $type     = $request->type ?? 'excel';
        $products = Product::with(['category:id,name'])->orderBy('name')->get();

        ActivityLog::record('EXPORT', "Data produk diekspor ({$type}).");

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('exports.products-pdf', [
                'products'  => $products,
                'generated' => now()->format('d/m/Y H:i'),
            ])->setPaper('A4', 'landscape');
            return $pdf->download('data-produk.pdf');
        }

        return Excel::download(new ProductsExport($products), 'data-produk.xlsx');
    }
}
