<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'category_id', 'stock', 'min_stock',
        'purchase_price', 'sale_price', 'unit', 'barcode', 'qr_code', 'images', 'is_active',
    ];

    protected $casts = [
        'images'         => 'array',
        'is_active'      => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price'     => 'decimal:2',
    ];

    // 1:M Inverse — Product belongsTo Category (RELASI 1)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // M:M — Product belongsToMany Suppliers (RELASI WAJIB M:M)
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
                    ->withPivot('purchase_price')
                    ->withTimestamps();
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    // Accessor — array URL gambar publik
    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])->map(
            fn($img) => Storage::url($img)
        )->values()->all();
    }

    // Decrement stok saat penjualan
    public function decrementStock(int $qty): bool
    {
        if ($this->stock < $qty) return false;
        $this->decrement('stock', $qty);
        return true;
    }
}
