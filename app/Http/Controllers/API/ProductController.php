<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category:id,name', 'suppliers:id,name,code'])
            ->when($request->search, fn($q, $s) => $q->search($s))
            ->when($request->category_id, fn($q, $id) => $q->where('category_id', $id))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->low_stock, fn($q) => $q->lowStock())
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $products->map(fn($p) => $this->productResource($p)),
            'meta'   => ['total' => $products->total(), 'last_page' => $products->lastPage(), 'current_page' => $products->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'min_stock'      => 'required|integer|min:0',
            'unit'           => 'required|string|max:20',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'supplier_ids'   => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'is_active'      => 'nullable|in:0,1,true,false',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $imagePaths = $this->imageService->uploadMany(
                $request->file('images'),
                'products'
            );
        }

        $product = Product::create([
            'code'           => 'PRD-' . strtoupper(Str::random(6)),
            'name'           => $request->name,
            'description'    => $request->description,
            'category_id'    => $request->category_id,
            'purchase_price' => $request->purchase_price,
            'sale_price'     => $request->sale_price,
            'stock'          => $request->stock,
            'min_stock'      => $request->min_stock,
            'unit'           => $request->unit,
            'barcode'        => $request->barcode ?? Str::random(12),
            'images'         => $imagePaths,  // JSON array of paths
            'is_active'      => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($request->has('supplier_ids')) {
            $product->suppliers()->sync($request->supplier_ids);
        }

        // Generate QR Code
        $qrPath = $this->generateQrCode($product->code);
        $product->update(['qr_code' => $qrPath]);

        ActivityLog::create([
            'user_id'      => $request->user()->id,
            'action'       => 'CREATE',
            'description'  => "Produk '{$product->name}' ditambahkan.",
            'subject_type' => 'Product',
            'subject_id'   => $product->id,
            'new_values'   => $product->toArray(),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Produk berhasil ditambahkan',
            'data'    => $product->load('category', 'suppliers'),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'name'            => 'sometimes|string|max:150',
            'images'          => 'nullable|array|max:5',
            'images.*'        => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_images'   => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        $imagePaths = $product->images ?? [];

        // Hapus gambar yang dipilih
        if ($request->has('remove_images') && is_array($request->remove_images)) {
            foreach ($request->remove_images as $path) {
                $this->imageService->delete($path);
                $imagePaths = array_values(array_filter($imagePaths, fn($p) => $p !== $path));
            }
        }

        // Tambah gambar baru
        if ($request->hasFile('images')) {
            $total = count($imagePaths) + count($request->file('images'));
            if ($total > 5) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Maksimal 5 gambar per produk',
                ], 422);
            }
            $newPaths   = $this->imageService->uploadMany($request->file('images'), 'products');
            $imagePaths = array_merge($imagePaths, $newPaths);
        }

        $oldValues = $product->toArray();
        $product->update([
            ...$request->only(['name', 'description', 'category_id', 'purchase_price', 'sale_price', 'stock', 'min_stock', 'unit', 'barcode']),
            'images'    => $imagePaths,
            'is_active' => $request->has('is_active')
                ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
                : $product->is_active,
        ]);

        if ($request->has('supplier_ids')) {
            $product->suppliers()->sync($request->supplier_ids ?? []);
        }

        ActivityLog::create([
            'user_id'      => $request->user()->id,
            'action'       => 'UPDATE',
            'description'  => "Produk '{$product->name}' diperbarui.",
            'subject_type' => 'Product',
            'subject_id'   => $product->id,
            'old_values'   => $oldValues,
            'new_values'   => $product->fresh()->toArray(),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Produk berhasil diperbarui',
            'data'    => $product->fresh()->load('category', 'suppliers'),
        ]);
    }

    private function generateQrCode(string $code): ?string
    {
        try {
            $generator = new \SimpleSoftwareIO\QrCode\Generator;
            $qr        = $generator->format('png')->size(200)->generate($code);
            $path      = "products/qr/{$code}.png";
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $qr);
            return $path;
        } catch (\Throwable $e) {
            Log::error('[QR] Gagal generate: ' . $e->getMessage());
            return null;
        }
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $this->productResource($product->load(['category', 'suppliers'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        ActivityLog::record(
            'DELETE',
            "Produk '{$product->name}' dihapus (soft delete).",
            'Product',
            $product->id,
            $product->toArray()
        );
        $product->delete();
        return response()->json(['status' => true, 'message' => 'Produk berhasil dihapus.']);
    }

    private function productResource(Product $product): array
    {
        return [
            'id'             => $product->id,
            'code'           => $product->code,
            'name'           => $product->name,
            'description'    => $product->description,
            'category'       => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'suppliers'      => $product->suppliers?->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'purchase_price' => $s->pivot->purchase_price ?? null]),
            'stock'          => $product->stock,
            'min_stock'      => $product->min_stock,
            'purchase_price' => $product->purchase_price,
            'sale_price'     => $product->sale_price,
            'unit'           => $product->unit,
            'barcode'        => $product->barcode,
            'qr_code'        => $product->qr_code,
            'image_urls'     => $product->image_urls,
            'is_active'      => $product->is_active,
            'is_low_stock'   => $product->stock <= $product->min_stock,
            'created_at'     => $product->created_at?->toDateTimeString(),
        ];
    }
}
