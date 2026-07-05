<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'supplier_id', 'user_id', 'total_amount',
        'status', 'order_date', 'received_date', 'notes',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'order_date'    => 'date',
        'received_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Update stok produk saat pembelian diterima
    public function receiveStock(): void
    {
        foreach ($this->items as $item) {
            $item->product->increment('stock', $item->quantity);
            $item->product->update(['purchase_price' => $item->unit_price]);
        }
        $this->update(['status' => 'received', 'received_date' => now()->toDateString()]);
    }
}
