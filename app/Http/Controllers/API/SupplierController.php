<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%"))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount('products')
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $suppliers->items(),
            'meta'   => ['total' => $suppliers->total(), 'last_page' => $suppliers->lastPage(), 'current_page' => $suppliers->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|unique:suppliers',
            'phone'   => 'required|string|max:20',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:100',
            'notes'   => 'nullable|string',
        ]);

        $data['code'] = 'SUP-' . str_pad(Supplier::count() + 1, 4, '0', STR_PAD_LEFT);
        $supplier = Supplier::create($data);

        ActivityLog::record('CREATE', "Supplier '{$supplier->name}' ditambahkan.", 'Supplier', $supplier->id, null, $supplier->toArray());

        return response()->json(['status' => true, 'message' => 'Supplier berhasil ditambahkan.', 'data' => $supplier], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $supplier->load(['products', 'purchaseTransactions' => fn($q) => $q->latest()->limit(10)])]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'phone'   => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:100',
            'notes'   => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $supplier->toArray();
        $supplier->update($data);

        ActivityLog::record('UPDATE', "Supplier '{$supplier->name}' diperbarui.", 'Supplier', $supplier->id, $oldValues, $supplier->fresh()->toArray());

        return response()->json(['status' => true, 'message' => 'Supplier berhasil diperbarui.', 'data' => $supplier]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->purchaseTransactions()->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Supplier memiliki riwayat transaksi, tidak dapat dihapus.'], 422);
        }

        ActivityLog::record('DELETE', "Supplier '{$supplier->name}' dihapus.", 'Supplier', $supplier->id, $supplier->toArray());
        $supplier->delete();

        return response()->json(['status' => true, 'message' => 'Supplier berhasil dihapus.']);
    }

    public function all(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'phone'])]);
    }
}
