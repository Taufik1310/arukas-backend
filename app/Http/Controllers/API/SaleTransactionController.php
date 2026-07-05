<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\SaleReceiptMail;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleTransaction;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SaleTransactionController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsApp
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sales = SaleTransaction::with('user:id,name')
            ->when($request->search, fn($q, $s) =>
            $q->where('code', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%"))
            ->when($request->payment_status, fn($q, $s) => $q->where('payment_status', $s))
            ->when($request->payment_method, fn($q, $m) => $q->where('payment_method', $m))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => true,
            'data'   => $sales->items(),
            'meta'   => ['total' => $sales->total(), 'last_page' => $sales->lastPage(), 'current_page' => $sales->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'total_amount'        => 'required|numeric|min:0',
            'payment_method'      => 'required|in:cash,transfer,midtrans',
            'paid_amount'         => 'required|numeric|min:0',
            'customer_name'       => 'nullable|string|max:100',
            'customer_phone'      => 'nullable|string|max:20',
            'customer_email'      => 'nullable|email|max:100',
            'discount'            => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            // Hitung total dan validasi stok
            $subtotal = 0;
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception(
                        "Stok {$product->name} tidak mencukupi. Tersedia: {$product->stock}"
                    );
                }

                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            // Buat transaksi
            $transaction = SaleTransaction::create([
                'code'             => 'TRX-' . strtoupper(Str::random(8)),
                'user_id'          => $request->user()->id,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'customer_email'   => $request->customer_email,
                'subtotal'         => $subtotal,
                'discount'         => $request->discount ?? 0,
                'tax'              => 0,
                'total_amount'     => $request->total_amount,
                'paid_amount'      => $request->paid_amount,
                'change_amount'    => max(0, $request->paid_amount - $request->total_amount),
                'payment_method'   => $request->payment_method,
                'payment_status'   => $request->payment_method === 'midtrans' ? 'pending' : 'paid',
                'notes'            => $request->notes,
            ]);

            // Simpan items + kurangi stok
            $lowStockProducts = [];
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                SaleItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'product_id'          => $product->id,
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'discount'            => 0,
                    'subtotal'            => $item['quantity'] * $item['unit_price'],
                ]);

                $product->decrement('stock', $item['quantity']);

                // Cek stok rendah setelah dikurangi
                if ($product->fresh()->is_low_stock) {
                    $lowStockProducts[] = $product->fresh();
                }
            }

            // Kirim notifikasi jika pembayaran langsung lunas
            if ($transaction->payment_status === 'paid') {
                $this->sendNotifications($transaction, $lowStockProducts);
            }

            // Activity log
            ActivityLog::create([
                'user_id'      => $request->user()->id,
                'action'       => 'CREATE',
                'description'  => "Transaksi penjualan #{$transaction->code} dibuat.",
                'subject_type' => 'SaleTransaction',
                'subject_id'   => $transaction->id,
                'new_values'   => $transaction->toArray(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Transaksi berhasil',
                'data'    => $transaction->load('items.product'),
            ], 201);
        });
    }

    // ── Notifikasi setelah transaksi berhasil ─────────────────────────────
    private function sendNotifications(
        SaleTransaction $transaction,
        array $lowStockProducts = []
    ): void {
        $transaction->loadMissing(['items.product', 'user']);

        // 1. Email struk ke pelanggan
        if ($transaction->customer_email) {
            try {
                Mail::to($transaction->customer_email)
                    ->queue(new SaleReceiptMail($transaction));
            } catch (\Throwable $e) {
                Log::error('[Email] Gagal kirim struk: ' . $e->getMessage());
            }
        }

        // 2. WhatsApp struk ke pelanggan
        if ($transaction->customer_phone) {
            try {
                $saleData = [
                    'code'           => $transaction->code,
                    'date'           => $transaction->created_at->format('d M Y, H:i'),
                    'cashier'        => $transaction->user->name ?? 'Kasir',
                    'total_amount'   => $transaction->total_amount,
                    'paid_amount'    => $transaction->paid_amount,
                    'change_amount'  => $transaction->change_amount,
                    'payment_method' => $transaction->payment_method,
                    'items'          => $transaction->items->map(fn($i) => [
                        'product_name' => $i->product->name ?? 'Produk',
                        'quantity'     => $i->quantity,
                        'unit_price'   => $i->unit_price,
                        'subtotal'     => $i->subtotal,
                    ])->toArray(),
                ];
                $this->whatsApp->sendSaleReceipt($transaction->customer_phone, $saleData);
            } catch (\Throwable $e) {
                Log::error('[WhatsApp] Gagal kirim struk: ' . $e->getMessage());
            }
        }

        // 3. Alert stok rendah ke admin
        if (!empty($lowStockProducts)) {
            $this->sendLowStockAlerts(collect($lowStockProducts));
        }
    }

    private function sendLowStockAlerts(\Illuminate\Support\Collection $products): void
    {
        $adminEmail = config('app.admin_email');
        $adminPhone = config('app.admin_phone');

        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->queue(new \App\Mail\LowStockAlertMail($products));
            } catch (\Throwable $e) {
                Log::error('[Email] Gagal kirim alert stok: ' . $e->getMessage());
            }
        }

        if ($adminPhone) {
            try {
                $this->whatsApp->sendLowStockAlert(
                    $adminPhone,
                    $products->map(fn($p) => [
                        'name'      => $p->name,
                        'stock'     => $p->stock,
                        'min_stock' => $p->min_stock,
                        'unit'      => $p->unit,
                    ])->toArray()
                );
            } catch (\Throwable $e) {
                Log::error('[WhatsApp] Gagal kirim alert stok: ' . $e->getMessage());
            }
        }
    }


    public function show(SaleTransaction $saleTransaction): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $saleTransaction->load(['items.product:id,name,code,unit', 'user:id,name']),
        ]);
    }
}
