<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PurchaseItem;
use App\Models\PurchaseTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $purchases = PurchaseTransaction::with(['supplier:id,name,code', 'user:id,name'])
            ->when($request->search, fn($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->supplier_id, fn($q, $id) => $q->where('supplier_id', $id))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('order_date', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('order_date', '<=', $d))
            ->latest('order_date')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $purchases->items(),
            'meta'   => ['total' => $purchases->total(), 'last_page' => $purchases->lastPage(), 'current_page' => $purchases->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id'              => 'required|exists:suppliers,id',
            'order_date'               => 'required|date',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'notes'                    => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $totalAmount = collect($data['items'])->reduce(fn($c, $i) => $c + ($i['unit_price'] * $i['quantity']), 0);

            $code = 'PUR-' . date('Ymd') . '-' . str_pad(PurchaseTransaction::whereDate('order_date', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $purchase = PurchaseTransaction::create([
                'code'        => $code,
                'supplier_id' => $data['supplier_id'],
                'user_id'     => auth()->id(),
                'total_amount'=> $totalAmount,
                'status'      => 'ordered',
                'order_date'  => $data['order_date'],
                'notes'       => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseItem::create([
                    'purchase_transaction_id' => $purchase->id,
                    'product_id'              => $item['product_id'],
                    'quantity'                => $item['quantity'],
                    'unit_price'              => $item['unit_price'],
                    'subtotal'                => $item['unit_price'] * $item['quantity'],
                ]);
            }

            ActivityLog::record('CREATE', "PO {$purchase->code} dibuat dari supplier ID {$purchase->supplier_id}.",
                'PurchaseTransaction', $purchase->id);

            return response()->json([
                'status'  => true,
                'message' => 'Purchase order berhasil dibuat.',
                'data'    => $purchase->load(['items.product', 'supplier']),
            ], 201);
        });
    }

    public function show(PurchaseTransaction $purchaseTransaction): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $purchaseTransaction->load(['items.product:id,name,code,unit,stock', 'supplier', 'user:id,name']),
        ]);
    }

    public function receive(PurchaseTransaction $purchaseTransaction): JsonResponse
    {
        if ($purchaseTransaction->status === 'received') {
            return response()->json(['status' => false, 'message' => 'Pembelian sudah pernah diterima.'], 422);
        }

        DB::transaction(function () use ($purchaseTransaction) {
            $purchaseTransaction->receiveStock();

            ActivityLog::record('UPDATE', "PO {$purchaseTransaction->code} diterima, stok produk diperbarui.",
                'PurchaseTransaction', $purchaseTransaction->id,
                ['status' => 'ordered'], ['status' => 'received']);
        });

        return response()->json(['status' => true, 'message' => 'Pembelian berhasil diterima, stok diperbarui.']);
    }
}
