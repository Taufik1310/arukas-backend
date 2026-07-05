<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount('products')
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $categories->items(),
            'meta'   => ['total' => $categories->total(), 'last_page' => $categories->lastPage(), 'current_page' => $categories->currentPage(), 'per_page' => $categories->perPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:1024',
            'is_active'   => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        $data['slug'] = Str::slug($data['name']);

        $category = Category::create($data);

        ActivityLog::record('CREATE', "Kategori '{$category->name}' ditambahkan.", 'Category', $category->id, null, $category->toArray());

        return response()->json(['status' => true, 'message' => 'Kategori berhasil ditambahkan.', 'data' => $category], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $category->load('products')]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:1024',
            'is_active'   => 'boolean',
        ]);

        $oldValues = $category->toArray();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);

        $category->update($data);

        ActivityLog::record('UPDATE', "Kategori '{$category->name}' diperbarui.", 'Category', $category->id, $oldValues, $category->fresh()->toArray());

        return response()->json(['status' => true, 'message' => 'Kategori berhasil diperbarui.', 'data' => $category]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Kategori masih memiliki produk, tidak dapat dihapus.'], 422);
        }

        ActivityLog::record('DELETE', "Kategori '{$category->name}' dihapus.", 'Category', $category->id, $category->toArray());
        $category->delete();

        return response()->json(['status' => true, 'message' => 'Kategori berhasil dihapus.']);
    }

    public function all(): JsonResponse
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'slug']);
        return response()->json(['status' => true, 'data' => $categories]);
    }
}
